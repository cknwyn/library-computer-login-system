using System;
using System.Diagnostics;
using System.IO;
using System.Threading;
using System.Threading.Tasks;

namespace LibraryKiosk.Services
{
    public class WatchdogService
    {
        private const string GuardExeName = "KioskGuard";
        private CancellationTokenSource? _cts;

        public void Start()
        {
            _cts = new CancellationTokenSource();
            Task.Run(() => MonitorLoop(_cts.Token));
        }

        public void Stop()
        {
            _cts?.Cancel();
        }

        private async Task MonitorLoop(CancellationToken token)
        {
            string baseDir = AppDomain.CurrentDomain.BaseDirectory;
            string guardPath = Path.Combine(baseDir, $"{GuardExeName}.exe");
            string mainPath = Process.GetCurrentProcess().MainModule?.FileName ?? "";

            while (!token.IsCancellationRequested)
            {
                try
                {
                    var processes = Process.GetProcessesByName(GuardExeName);
                    if (processes.Length == 0 && File.Exists(guardPath))
                    {
                        Process.Start(new ProcessStartInfo(guardPath, $"\"{mainPath}\"") 
                        { 
                            UseShellExecute = true,
                            CreateNoWindow = true,
                            WindowStyle = ProcessWindowStyle.Hidden
                        });
                    }
                }
                catch { /* Silent failure */ }

                await Task.Delay(10000, token);
            }
        }
    }
}
