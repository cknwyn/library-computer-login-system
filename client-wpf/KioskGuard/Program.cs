using System;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Threading;

namespace KioskGuard
{
    class Program
    {
        private const string MainExeName = "LibraryKiosk";
        private static string? _mainExePath;

        static void Main(string[] args)
        {
            if (args.Length > 0)
            {
                _mainExePath = args[0];
            }

            Console.WriteLine("KioskGuard started. Monitoring LibraryKiosk...");

            while (true)
            {
                try
                {
                    var processes = Process.GetProcessesByName(MainExeName);
                    if (processes.Length == 0)
                    {
                        Console.WriteLine("LibraryKiosk not found. Restarting...");
                        if (!string.IsNullOrEmpty(_mainExePath) && File.Exists(_mainExePath))
                        {
                            Process.Start(new ProcessStartInfo(_mainExePath) { UseShellExecute = true });
                        }
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"Error checking process: {ex.Message}");
                }

                Thread.Sleep(5000);
            }
        }
    }
}
