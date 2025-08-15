// =================================================================================
//  新闻调度器 更新程序 v1.4 (自我更新版)
//  作者: Gemini
//  更新日期: 2024-08-08
//
//  功能:
//  - [新增] 实现了静默自我更新功能。程序会优先检查自身更新，并在发现新版本时
//    自动下载、替换并重启，整个过程无需用户干预。
//  - [修复] 恢复使用 wWinMain 作为唯一的程序入口点。
//  - [新增] 首次运行时显示带进度条的安装向导UI。
//  - [新增] 安装完成后提示用户进行初始配置。
//  - [新增] 发现更新时，在弹窗中显示详细的更新描述。
//  - [新增] 程序现在拥有系统托盘图标，并可通过菜单操作。
//  - [新增] 实现了与主程序的生命周期绑定（随主程序退出）。
// =================================================================================

#include <Windows.h>
#include <string>
#include <thread>
#include <chrono>
#include <fstream>
#include <wininet.h>
#include <shlwapi.h>
#include <urlmon.h>
#include <vector>
#include <sstream>
#include <CommCtrl.h>
#include "Updater.h" 

#pragma comment(lib, "wininet.lib")
#pragma comment(lib, "shlwapi.lib")
#pragma comment(lib, "urlmon.lib")
#pragma comment(lib, "user32.lib")
#pragma comment(lib, "comctl32.lib")
#pragma comment(linker,"/manifestdependency:\"type='win32' name='Microsoft.Windows.Common-Controls' version='6.0.0.0' processorArchitecture='*' publicKeyToken='6595b64144ccf1df' language='*'\"")


using namespace std;
using namespace std::chrono_literals;

// --- 全局常量 ---
const wstring TARGET_DIR = L"D:\\news\\";
const wstring VERSION_FILE = TARGET_DIR + L"versions.ini";
const wchar_t* const UPDATER_MUTEX = L"Global\\NewsSchedulerUpdaterMutex_UNIQUE_E7A9";
const wchar_t* const IPC_WINDOW_CLASS = L"NewsSchedulerIPCWindowClass";
const wchar_t* const MAIN_APP_WND_CLASS = L"NewsSchedulerMainWindowClass";
const wchar_t* const UPDATER_APP_NAME = L"新闻调度器更新服务";
const wchar_t* const UPDATER_VERSION = L"1.4.0"; // [NEW] Current version of the updater itself

// --- 通信消息 ---
#define WM_LOG_MESSAGE (WM_APP + 100)
#define WM_CHECK_UPDATE_NOW (WM_APP + 101)
#define WM_APP_TRAY_MSG (WM_APP + 102)

// --- 自定义窗口消息 ---
#define WM_SETUP_UPDATE_STATUS (WM_APP + 200)
#define WM_SETUP_UPDATE_PROGRESS (WM_APP + 201)
#define WM_SETUP_COMPLETE (WM_APP + 202)


// --- API URLs ---
const wstring API_BASE_URL = L"http://news.lcez.top/api";
const wstring UPLOAD_PHP_URL = API_BASE_URL + L"/upload.php";
const wstring DOWNLOAD_HANDLER_URL = L"http://news.lcez.top/management/download_handler.php";
const wstring VC_REDIST_URL = L"http://news.lcez.top/uploads/support_tools/VC_redist.x64.exe";


// --- 全局变量 ---
atomic_bool g_bRunning(true);
HWND g_hMainAppWnd = NULL;
HWND g_hSetupDlg = NULL;
HINSTANCE g_hInstance = NULL;

// --- 结构体 ---
struct UpdateInfo {
    wstring component;
    wstring serverVersion;
    wstring serverFilename;
    wstring serverDescription;
};

// --- 辅助函数 ---
wstring utf8_to_wstring(const string& str) {
    if (str.empty()) return wstring();
    int size_needed = MultiByteToWideChar(CP_UTF8, 0, &str[0], (int)str.size(), NULL, 0);
    wstring wstrTo(size_needed, 0);
    MultiByteToWideChar(CP_UTF8, 0, &str[0], (int)str.size(), &wstrTo[0], size_needed);
    return wstrTo;
}

string wstring_to_utf8(const wstring& wstr) {
    if (wstr.empty()) return string();
    int size_needed = WideCharToMultiByte(CP_UTF8, 0, &wstr[0], (int)wstr.size(), NULL, 0, NULL, NULL);
    string strTo(size_needed, 0);
    WideCharToMultiByte(CP_UTF8, 0, &wstr[0], (int)wstr.size(), &strTo[0], size_needed, NULL, NULL);
    return strTo;
}


// --- 函数原型 ---
void SendLogToMainApp(const wstring& logMessage);
bool IsVC2015_2022_x64_RedistInstalled();
bool DownloadFileWithProgress(const wstring& url, const wstring& savePath, int progressControlId);
bool ExecuteAndWait(const wstring& path, const wstring& params);
void InitialSetupThread();
void CheckForUpdates(bool isManual);
vector<UpdateInfo> GetAvailableUpdates();
wstring GetLatestVersionInfo(const wstring& type, wstring& outFilename, wstring& outDescription);
wstring GetLocalVersion(const wstring& type);
void SaveLocalVersion(const wstring& type, const wstring& version);
void CreateTrayIcon(HWND hWnd);
void RemoveTrayIcon(HWND hWnd);
void ShowTrayMenu(HWND hWnd);

// =================================================================================
// SECTION: 下载与进度回调
// =================================================================================
class DownloadProgressCallback : public IBindStatusCallback {
    int m_progressControlId;
public:
    DownloadProgressCallback(int controlId) : m_progressControlId(controlId) {}
    // IUnknown
    STDMETHOD(QueryInterface)(REFIID riid, void** ppvObject) { *ppvObject = NULL; return E_NOINTERFACE; }
    STDMETHOD_(ULONG, AddRef)() { return 1; }
    STDMETHOD_(ULONG, Release)() { return 1; }
    // IBindStatusCallback
    STDMETHOD(OnStartBinding)(DWORD, IBinding*) { return E_NOTIMPL; }
    STDMETHOD(GetPriority)(LONG*) { return E_NOTIMPL; }
    STDMETHOD(OnLowResource)(DWORD) { return E_NOTIMPL; }
    STDMETHOD(OnProgress)(ULONG ulProgress, ULONG ulProgressMax, ULONG, LPCWSTR) {
        if (g_hSetupDlg && ulProgressMax > 0) {
            int percentage = (int)((double)ulProgress / ulProgressMax * 100.0);
            PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_PROGRESS, (WPARAM)m_progressControlId, (LPARAM)percentage);
        }
        return S_OK;
    }
    STDMETHOD(OnStopBinding)(HRESULT, LPCWSTR) { return E_NOTIMPL; }
    STDMETHOD(GetBindInfo)(DWORD*, BINDINFO*) { return E_NOTIMPL; }
    STDMETHOD(OnDataAvailable)(DWORD, DWORD, FORMATETC*, STGMEDIUM*) { return E_NOTIMPL; }
    STDMETHOD(OnObjectAvailable)(REFIID, IUnknown*) { return E_NOTIMPL; }
};

bool DownloadFileWithProgress(const wstring& url, const wstring& savePath, int progressControlId) {
    SendLogToMainApp(L"正在下载: " + url + L" 到 " + savePath);
    DownloadProgressCallback callback(progressControlId);
    if (S_OK == URLDownloadToFileW(NULL, url.c_str(), savePath.c_str(), 0, &callback)) {
        SendLogToMainApp(L"下载成功。");
        return true;
    }
    SendLogToMainApp(L"下载失败！");
    return false;
}

// =================================================================================
// SECTION: 核心逻辑
// =================================================================================

void SendLogToMainApp(const wstring& logMessage) {
    if (!g_hMainAppWnd || !IsWindow(g_hMainAppWnd)) {
        g_hMainAppWnd = FindWindowW(MAIN_APP_WND_CLASS, NULL);
    }
    if (g_hMainAppWnd) {
        COPYDATASTRUCT cds;
        cds.dwData = WM_LOG_MESSAGE;
        cds.cbData = (DWORD)(logMessage.length() + 1) * sizeof(wchar_t);
        cds.lpData = (PVOID)logMessage.c_str();
        SendMessage(g_hMainAppWnd, WM_COPYDATA, (WPARAM)NULL, (LPARAM)&cds);
    }
}

bool IsVC2015_2022_x64_RedistInstalled() {
    HKEY hKey;
    const wchar_t* regKey = L"SOFTWARE\\Microsoft\\VisualStudio\\14.0\\VC\\Runtimes\\x64";
    LONG result = RegOpenKeyExW(HKEY_LOCAL_MACHINE, regKey, 0, KEY_READ, &hKey);
    if (result == ERROR_SUCCESS) {
        RegCloseKey(hKey);
        return true;
    }
    return false;
}

bool ExecuteAndWait(const wstring& path, const wstring& params) {
    SHELLEXECUTEINFOW sei = { sizeof(sei) };
    sei.fMask = SEE_MASK_NOCLOSEPROCESS;
    sei.lpFile = path.c_str();
    sei.lpParameters = params.c_str();
    sei.nShow = SW_SHOW;

    if (ShellExecuteExW(&sei)) {
        if (sei.hProcess) {
            WaitForSingleObject(sei.hProcess, INFINITE);
            CloseHandle(sei.hProcess);
            return true;
        }
    }
    return false;
}

void InitialSetupThread() {
    SendLogToMainApp(L"首次运行检查...");
    CreateDirectoryW(TARGET_DIR.c_str(), NULL);

    PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_STATUS, 0, (LPARAM)_wcsdup(L"正在检查VC++运行环境..."));
    if (!IsVC2015_2022_x64_RedistInstalled()) {
        SendLogToMainApp(L"未检测到VC++环境，开始下载...");
        PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_STATUS, 0, (LPARAM)_wcsdup(L"未检测到VC++环境，正在下载..."));
        wstring vcRedistPath = TARGET_DIR + L"VC_redist.x64.exe";
        if (DownloadFileWithProgress(VC_REDIST_URL, vcRedistPath, IDC_PROGRESS_VC)) {
            SendLogToMainApp(L"VC++环境下载完成，正在启动安装...");
            PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_STATUS, 0, (LPARAM)_wcsdup(L"VC++环境下载完成，请根据提示完成安装..."));
            if (ExecuteAndWait(vcRedistPath, L"/install /passive /norestart")) {
                SendLogToMainApp(L"VC++环境安装完成。");
                if (!IsVC2015_2022_x64_RedistInstalled()) {
                    SendLogToMainApp(L"错误：VC++环境安装后仍未检测到！");
                    MessageBoxW(g_hSetupDlg, L"VC++环境安装失败，程序可能无法正常运行。", L"安装错误", MB_OK | MB_ICONERROR);
                }
            }
            else {
                SendLogToMainApp(L"VC++环境安装程序启动失败。");
                MessageBoxW(g_hSetupDlg, L"VC++环境安装程序启动失败。", L"安装错误", MB_OK | MB_ICONERROR);
            }
        }
    }
    else {
        SendLogToMainApp(L"VC++环境已存在。");
        PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_STATUS, 0, (LPARAM)_wcsdup(L"VC++环境已存在。"));
        PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_PROGRESS, (WPARAM)IDC_PROGRESS_VC, (LPARAM)100);
    }

    // 下载主程序
    PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_STATUS, 0, (LPARAM)_wcsdup(L"正在下载主程序..."));
    wstring newsVersion, newsFilename, newsDesc;
    newsVersion = GetLatestVersionInfo(L"newsapp", newsFilename, newsDesc);
    if (!newsVersion.empty()) {
        if (DownloadFileWithProgress(DOWNLOAD_HANDLER_URL + L"?type=program&file=" + newsFilename, TARGET_DIR + newsFilename, IDC_PROGRESS_NEWS)) {
            SaveLocalVersion(L"newsapp", newsVersion);
        }
    }

    // 下载配置工具
    PostMessage(g_hSetupDlg, WM_SETUP_UPDATE_STATUS, 0, (LPARAM)_wcsdup(L"正在下载配置工具..."));
    wstring configVersion, configFilename, configDesc;
    configVersion = GetLatestVersionInfo(L"configurator", configFilename, configDesc);
    if (!configVersion.empty()) {
        if (DownloadFileWithProgress(DOWNLOAD_HANDLER_URL + L"?type=program&file=" + configFilename, TARGET_DIR + configFilename, IDC_PROGRESS_CONFIG)) {
            SaveLocalVersion(L"configurator", configVersion);
        }
    }

    PostMessage(g_hSetupDlg, WM_SETUP_COMPLETE, 0, 0);
}

// [MODIFIED] CheckForUpdates now handles self-update first
void CheckForUpdates(bool isManual) {
    if (isManual) {
        SendLogToMainApp(L"收到手动检查更新请求...");
    }
    else {
        SendLogToMainApp(L"开始例行更新检查...");
    }

    // 1. Self-update check
    wstring selfVersion, selfFilename, selfDesc;
    selfVersion = GetLatestVersionInfo(L"updater", selfFilename, selfDesc);
    if (!selfVersion.empty() && selfVersion > UPDATER_VERSION) {
        SendLogToMainApp(L"发现更新程序新版本: " + selfVersion + L"，开始静默更新...");
        wstring downloadUrl = DOWNLOAD_HANDLER_URL + L"?type=program&file=" + selfFilename;
        wstring newUpdaterPath = TARGET_DIR + L"Updater_new.exe";

        if (DownloadFileWithProgress(downloadUrl, newUpdaterPath, 0)) {
            wchar_t currentExePath[MAX_PATH];
            GetModuleFileNameW(NULL, currentExePath, MAX_PATH);

            wstring batFilePath = TARGET_DIR + L"updater_self_update.bat";
            wofstream batFile(batFilePath);
            if (batFile.is_open()) {
                batFile << L"@echo off\r\n"
                    << L"chcp 65001 > nul\r\n"
                    << L"echo Waiting for old updater to close...\r\n"
                    << L"timeout /t 3 /nobreak > nul\r\n"
                    << L"echo Replacing updater...\r\n"
                    << L"del \"" << currentExePath << L"\"\r\n"
                    << L"move /Y \"" << newUpdaterPath << L"\" \"" << currentExePath << L"\"\r\n"
                    << L"echo Restarting new updater...\r\n"
                    << L"start \"\" \"" << currentExePath << L"\"\r\n"
                    << L"del \"%~f0\"\r\n";
                batFile.close();

                ShellExecuteW(NULL, L"open", batFilePath.c_str(), NULL, TARGET_DIR.c_str(), SW_HIDE);
                g_bRunning = false;
                PostQuitMessage(0);
                return; // Exit immediately to allow update
            }
        }
    }

    // 2. Check for other components
    vector<UpdateInfo> updates = GetAvailableUpdates();

    if (updates.empty()) {
        if (isManual) {
            MessageBoxW(NULL, L"所有组件都已是最新版本。", L"检查更新", MB_OK | MB_ICONINFORMATION);
        }
        SendLogToMainApp(L"所有组件都已是最新版本。");
        return;
    }

    wstringstream ss;
    ss << L"发现以下更新：\r\n\r\n";
    for (const auto& update : updates) {
        ss << L"组件: " << update.component << L" (新版本: " << update.serverVersion << L")\r\n";
        ss << L"更新内容:\r\n" << update.serverDescription << L"\r\n\r\n";
    }

    int msgboxID = MessageBoxW(NULL, ss.str().c_str(), L"发现新版本", MB_YESNO | MB_ICONINFORMATION);

    if (msgboxID == IDYES) {
        SendLogToMainApp(L"用户选择更新。");
        for (const auto& update : updates) {
            wstring downloadUrl = DOWNLOAD_HANDLER_URL + L"?type=program&file=" + update.serverFilename;
            wstring savePath = TARGET_DIR + update.serverFilename;
            if (DownloadFileWithProgress(downloadUrl, savePath, 0)) {
                SaveLocalVersion(update.component, update.serverVersion);
                SendLogToMainApp(update.component + L" 已更新至版本 " + update.serverVersion);
            }
            else {
                SendLogToMainApp(L"下载 " + update.component + L" 新版本失败。");
                MessageBoxW(NULL, (L"下载 " + update.component + L" 失败！").c_str(), L"更新错误", MB_OK | MB_ICONERROR);
            }
        }
        MessageBoxW(NULL, L"所有选定组件更新完成。", L"更新完成", MB_OK | MB_ICONINFORMATION);
    }
    else {
        SendLogToMainApp(L"用户选择暂不更新。");
    }
}

vector<UpdateInfo> GetAvailableUpdates() {
    vector<UpdateInfo> updates;
    const vector<wstring> components = { L"newsapp", L"configurator" };

    for (const auto& comp : components) {
        wstring serverVersion, serverFilename, serverDescription;
        serverVersion = GetLatestVersionInfo(comp, serverFilename, serverDescription);
        wstring localVersion = GetLocalVersion(comp);

        if (!serverVersion.empty() && (serverVersion > localVersion || localVersion.empty())) {
            updates.push_back({ comp, serverVersion, serverFilename, serverDescription });
        }
    }
    return updates;
}

wstring GetLatestVersionInfo(const wstring& type, wstring& outFilename, wstring& outDescription) {
    wstring serverVersion;
    wstring apiUrl = UPLOAD_PHP_URL + L"?action=latest&type=" + type;

    HINTERNET hInternet = InternetOpenW(L"Updater/1.1", INTERNET_OPEN_TYPE_DIRECT, NULL, NULL, 0);
    if (hInternet) {
        HINTERNET hConnect = InternetOpenUrlW(hInternet, apiUrl.c_str(), NULL, 0, INTERNET_FLAG_RELOAD | INTERNET_FLAG_NO_CACHE_WRITE, 0);
        if (hConnect) {
            string jsonResponse;
            char buffer[4096];
            DWORD bytesRead = 0;
            while (InternetReadFile(hConnect, buffer, sizeof(buffer) - 1, &bytesRead) && bytesRead > 0) {
                jsonResponse.append(buffer, bytesRead);
            }

            if (!jsonResponse.empty()) {
                try {
                    size_t verPos = jsonResponse.find("\"version\":\"");
                    if (verPos != string::npos) {
                        verPos += 11;
                        serverVersion = utf8_to_wstring(jsonResponse.substr(verPos, jsonResponse.find('"', verPos) - verPos));
                    }
                    size_t filePos = jsonResponse.find("\"filename\":\"");
                    if (filePos != string::npos) {
                        filePos += 12;
                        outFilename = utf8_to_wstring(jsonResponse.substr(filePos, jsonResponse.find('"', filePos) - filePos));
                    }
                    size_t descPos = jsonResponse.find("\"description\":\"");
                    if (descPos != string::npos) {
                        descPos += 15;
                        string desc_utf8 = jsonResponse.substr(descPos, jsonResponse.find('"', descPos) - descPos);
                        outDescription = utf8_to_wstring(desc_utf8);
                    }
                }
                catch (...) {}
            }
            InternetCloseHandle(hConnect);
        }
        InternetCloseHandle(hInternet);
    }
    return serverVersion;
}

wstring GetLocalVersion(const wstring& type) {
    wchar_t version[64];
    GetPrivateProfileStringW(L"Versions", type.c_str(), L"", version, 64, VERSION_FILE.c_str());
    return wstring(version);
}

void SaveLocalVersion(const wstring& type, const wstring& version) {
    WritePrivateProfileStringW(L"Versions", type.c_str(), version.c_str(), VERSION_FILE.c_str());
}


void UpdateThread() {
    this_thread::sleep_for(chrono::minutes(5));

    while (g_bRunning) {
        if (FindWindowW(MAIN_APP_WND_CLASS, NULL) == NULL) {
            SendLogToMainApp(L"主程序已关闭，更新程序即将退出。");
            g_bRunning = false;
            PostQuitMessage(0);
            break;
        }
        CheckForUpdates(false);
        this_thread::sleep_for(chrono::hours(1));
    }
}

INT_PTR CALLBACK SetupDlgProc(HWND hDlg, UINT message, WPARAM wParam, LPARAM lParam) {
    switch (message) {
    case WM_INITDIALOG:
        g_hSetupDlg = hDlg;
        SendMessage(GetDlgItem(hDlg, IDC_PROGRESS_VC), PBM_SETRANGE, 0, MAKELPARAM(0, 100));
        SendMessage(GetDlgItem(hDlg, IDC_PROGRESS_NEWS), PBM_SETRANGE, 0, MAKELPARAM(0, 100));
        SendMessage(GetDlgItem(hDlg, IDC_PROGRESS_CONFIG), PBM_SETRANGE, 0, MAKELPARAM(0, 100));
        thread(InitialSetupThread).detach();
        return (INT_PTR)TRUE;
    case WM_SETUP_UPDATE_STATUS:
        SetDlgItemTextW(hDlg, IDC_STATUS_TEXT, (LPCWSTR)lParam);
        free((void*)lParam);
        return (INT_PTR)TRUE;
    case WM_SETUP_UPDATE_PROGRESS:
        SendDlgItemMessage(hDlg, (int)wParam, PBM_SETPOS, (WPARAM)lParam, 0);
        return (INT_PTR)TRUE;
    case WM_SETUP_COMPLETE:
        SetDlgItemTextW(hDlg, IDC_STATUS_TEXT, L"安装完成！");
        MessageBoxW(hDlg, L"核心组件已安装完成。接下来，请运行配置工具进行初始设置。", L"安装完成", MB_OK | MB_ICONINFORMATION);
        ShellExecuteW(NULL, L"open", (TARGET_DIR + L"Configurator.exe").c_str(), NULL, TARGET_DIR.c_str(), SW_SHOW);
        EndDialog(hDlg, IDOK);
        return (INT_PTR)TRUE;
    case WM_COMMAND:
        if (LOWORD(wParam) == IDCANCEL) {
            EndDialog(hDlg, IDCANCEL);
        }
        return (INT_PTR)TRUE;
    }
    return (INT_PTR)FALSE;
}

LRESULT CALLBACK IPCWndProc(HWND hWnd, UINT message, WPARAM wParam, LPARAM lParam) {
    switch (message) {
    case WM_APP_TRAY_MSG:
        if (lParam == WM_RBUTTONUP) {
            ShowTrayMenu(hWnd);
        }
        break;
    case WM_COMMAND:
        switch (LOWORD(wParam)) {
        case ID_TRAY_CHECKUPDATE_NOW:
            thread(CheckForUpdates, true).detach();
            break;
        case ID_TRAY_EXIT_UPDATER:
            PostQuitMessage(0);
            break;
        }
        break;
    case WM_COPYDATA: {
        COPYDATASTRUCT* pcds = (COPYDATASTRUCT*)lParam;
        if (pcds->dwData == WM_CHECK_UPDATE_NOW) {
            thread(CheckForUpdates, true).detach();
        }
        return TRUE;
    }
    case WM_DESTROY:
        PostQuitMessage(0);
        break;
    default:
        return DefWindowProc(hWnd, message, wParam, lParam);
    }
    return 0;
}

// =================================================================================
// SECTION: 托盘图标
// =================================================================================
void CreateTrayIcon(HWND hWnd) {
    NOTIFYICONDATAW nid = {};
    nid.cbSize = sizeof(nid);
    nid.hWnd = hWnd;
    nid.uID = 1;
    nid.uFlags = NIF_ICON | NIF_MESSAGE | NIF_TIP;
    nid.uCallbackMessage = WM_APP_TRAY_MSG;
    nid.hIcon = LoadIcon(g_hInstance, MAKEINTRESOURCE(IDI_APPICON));
    wcscpy_s(nid.szTip, UPDATER_APP_NAME);
    if (!Shell_NotifyIconW(NIM_ADD, &nid)) {
        // Log failure if needed
    }
}

void RemoveTrayIcon(HWND hWnd) {
    NOTIFYICONDATAW nid = {};
    nid.cbSize = sizeof(nid);
    nid.hWnd = hWnd;
    nid.uID = 1;
    Shell_NotifyIconW(NIM_DELETE, &nid);
}

void ShowTrayMenu(HWND hWnd) {
    POINT pt;
    GetCursorPos(&pt);
    HMENU hMenu = CreatePopupMenu();
    if (hMenu) {
        InsertMenuW(hMenu, -1, MF_BYPOSITION, ID_TRAY_CHECKUPDATE_NOW, L"立即检查更新");
        InsertMenuW(hMenu, -1, MF_BYPOSITION | MF_SEPARATOR, 0, nullptr);
        InsertMenuW(hMenu, -1, MF_BYPOSITION, ID_TRAY_EXIT_UPDATER, L"退出更新服务");
        SetForegroundWindow(hWnd);
        TrackPopupMenu(hMenu, TPM_BOTTOMALIGN | TPM_LEFTALIGN, pt.x, pt.y, 0, hWnd, NULL);
        DestroyMenu(hMenu);
    }
}

// =================================================================================
// SECTION: 主程序入口
// =================================================================================
int WINAPI wWinMain(HINSTANCE hInstance, HINSTANCE, LPWSTR lpCmdLine, int) {
    g_hInstance = hInstance;
    HANDLE hMutex = CreateMutexW(NULL, TRUE, UPDATER_MUTEX);
    if (hMutex == NULL || GetLastError() == ERROR_ALREADY_EXISTS) {
        if (hMutex) CloseHandle(hMutex);
        return 0;
    }

    InitCommonControls();

    bool isFirstRun = !PathFileExistsW(VERSION_FILE.c_str());

    if (isFirstRun) {
        DialogBox(hInstance, MAKEINTRESOURCE(IDD_SETUP), NULL, SetupDlgProc);
    }

    WNDCLASSEXW wcex = { 0 };
    wcex.cbSize = sizeof(WNDCLASSEX);
    wcex.lpfnWndProc = IPCWndProc;
    wcex.hInstance = hInstance;
    wcex.lpszClassName = IPC_WINDOW_CLASS;
    RegisterClassExW(&wcex);

    HWND hIPCWnd = CreateWindowW(IPC_WINDOW_CLASS, L"NewsSchedulerIPC", WS_OVERLAPPEDWINDOW,
        CW_USEDEFAULT, 0, CW_USEDEFAULT, 0, NULL, NULL, hInstance, NULL);

    if (!hIPCWnd) {
        CloseHandle(hMutex);
        return 1;
    }

    CreateTrayIcon(hIPCWnd);

    g_hMainAppWnd = FindWindowW(MAIN_APP_WND_CLASS, NULL);

    thread(UpdateThread).detach();

    MSG msg;
    while (g_bRunning && GetMessage(&msg, NULL, 0, 0)) {
        TranslateMessage(&msg);
        DispatchMessage(&msg);
    }

    RemoveTrayIcon(hIPCWnd);
    CloseHandle(hMutex);
    return (int)msg.wParam;
}
