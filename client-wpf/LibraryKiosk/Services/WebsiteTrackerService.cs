using System;
using System.Diagnostics;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Automation;
using LibraryKiosk.Services;

namespace LibraryKiosk.Services
{
    public class WebsiteTrackerService
    {
        private readonly ApiService _apiService;
        private readonly string _sessionToken;
        private CancellationTokenSource? _cts;
        private string? _lastUrl;

        public WebsiteTrackerService(ApiService apiService, string sessionToken)
        {
            _apiService = apiService;
            _sessionToken = sessionToken;
        }

        public void Start()
        {
            LogToFile("Tracker Started");
            _cts = new CancellationTokenSource();
            Task.Run(() => TrackerLoop(_cts.Token));
        }

        public void Stop()
        {
            _cts?.Cancel();
        }

        private async Task TrackerLoop(CancellationToken token)
        {
            while (!token.IsCancellationRequested)
            {
                try
                {
                    string? currentUrl = GetActiveBrowserUrl();
                    if (!string.IsNullOrEmpty(currentUrl) && currentUrl != _lastUrl)
                    {
                        LogToFile($"Detected URL: {currentUrl}");
                        string title = GetActiveWindowTitle() ?? "Unknown Title";
                        
                        string response = await _apiService.TrackWebsiteAsync(_sessionToken, currentUrl, title);
                        LogToFile($"Server Response: {response}");
                        
                        if (response.Contains("\"success\":true"))
                        {
                            _lastUrl = currentUrl;
                        }
                    }
                }
                catch (Exception ex)
                {
                    LogToFile($"Loop Error: {ex.Message}");
                }

                await Task.Delay(5000, token); // Check every 5 seconds
            }
        }

        private string? GetActiveBrowserUrl()
        {
            try
            {
                var foregroundWindow = GetForegroundWindow();
                if (foregroundWindow == IntPtr.Zero) return null;

                var element = AutomationElement.FromHandle(foregroundWindow);
                if (element == null) return null;

                string processName = GetProcessName(foregroundWindow).ToLower();

                // Optimized search for browser URL bar
                if (processName.Contains("chrome") || processName.Contains("msedge"))
                {
                    // 1. Try common AutomationId for Chrome/Edge
                    var urlBar = element.FindFirst(TreeScope.Descendants, 
                        new PropertyCondition(AutomationElement.AutomationIdProperty, "addressbar_container"));
                    
                    if (urlBar == null)
                    {
                        // 2. Try searching for the Edit control with specific name
                        urlBar = element.FindFirst(TreeScope.Descendants, 
                            new AndCondition(
                                new PropertyCondition(AutomationElement.ControlTypeProperty, ControlType.Edit),
                                new OrCondition(
                                    new PropertyCondition(AutomationElement.NameProperty, "Address and search bar"),
                                    new PropertyCondition(AutomationElement.NameProperty, "Address bar")
                                )
                            ));
                    }

                    if (urlBar == null)
                    {
                        // 3. Last resort: Find first Edit control (can be noisy)
                        urlBar = element.FindFirst(TreeScope.Descendants, 
                            new PropertyCondition(AutomationElement.ControlTypeProperty, ControlType.Edit));
                    }

                    if (urlBar != null)
                    {
                        object pattern;
                        if (urlBar.TryGetCurrentPattern(ValuePattern.Pattern, out pattern))
                        {
                            return ((ValuePattern)pattern).Current.Value;
                        }
                    }
                }
                else if (processName.Contains("firefox"))
                {
                    var urlBar = element.FindFirst(TreeScope.Descendants, 
                        new AndCondition(
                            new PropertyCondition(AutomationElement.ControlTypeProperty, ControlType.Edit),
                            new OrCondition(
                                new PropertyCondition(AutomationElement.NameProperty, "Search with Google or enter address"),
                                new PropertyCondition(AutomationElement.NameProperty, "Address and search bar")
                            )
                        ));

                    if (urlBar != null)
                    {
                        object pattern;
                        if (urlBar.TryGetCurrentPattern(ValuePattern.Pattern, out pattern))
                        {
                            return ((ValuePattern)pattern).Current.Value;
                        }
                    }
                }
            }
            catch (Exception ex) 
            { 
                LogToFile($"Automation Error: {ex.Message}");
            }
            
            return null;
        }

        private void LogToFile(string message)
        {
            try
            {
                string logPath = System.IO.Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "tracker_log.txt");
                System.IO.File.AppendAllText(logPath, $"[{DateTime.Now:HH:mm:ss}] {message}{Environment.NewLine}");
            }
            catch { }
        }

        private string? GetActiveWindowTitle()
        {
            var foregroundWindow = GetForegroundWindow();
            if (foregroundWindow == IntPtr.Zero) return null;
            
            int length = GetWindowTextLength(foregroundWindow);
            if (length == 0) return null;

            var sb = new System.Text.StringBuilder(length + 1);
            GetWindowText(foregroundWindow, sb, sb.Capacity);
            return sb.ToString();
        }

        private string GetProcessName(IntPtr handle)
        {
            uint processId;
            GetWindowThreadProcessId(handle, out processId);
            var process = Process.GetProcessById((int)processId);
            return process.ProcessName;
        }

        // P/Invoke
        [System.Runtime.InteropServices.DllImport("user32.dll")]
        private static extern IntPtr GetForegroundWindow();

        [System.Runtime.InteropServices.DllImport("user32.dll")]
        private static extern int GetWindowText(IntPtr hWnd, System.Text.StringBuilder text, int count);

        [System.Runtime.InteropServices.DllImport("user32.dll")]
        private static extern int GetWindowTextLength(IntPtr hWnd);

        [System.Runtime.InteropServices.DllImport("user32.dll")]
        private static extern uint GetWindowThreadProcessId(IntPtr hWnd, out uint lpdwProcessId);
    }
}
