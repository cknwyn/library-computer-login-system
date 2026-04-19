using System;
using System.IO;
using System.Windows;
using Microsoft.Extensions.Configuration;
using LibraryKiosk.Services;

namespace LibraryKiosk
{
    public partial class App : Application
    {
        private KeyboardHook? _keyboardHook;
        private WatchdogService? _watchdogService;
        public IConfiguration? Configuration { get; private set; }

        protected override void OnStartup(StartupEventArgs e)
        {
            base.OnStartup(e);

            // Load configuration
            var builder = new ConfigurationBuilder()
                .SetBasePath(Directory.GetCurrentDirectory())
                .AddJsonFile("appsettings.json", optional: false, reloadOnChange: true);

            Configuration = builder.Build();

            // Set up UI
            var apiBaseUrl = Configuration["LibraryKiosk:ApiBaseUrl"] ?? "http://localhost/library-system/api";
            var terminalCode = Configuration["LibraryKiosk:TerminalCode"] ?? "PC-04";
            var kioskMode = Configuration.GetValue<bool>("LibraryKiosk:KioskMode");

            // Initialize services
            _keyboardHook = new KeyboardHook();
            _watchdogService = new WatchdogService();

            if (kioskMode)
            {
                _keyboardHook.Install();
                _watchdogService.Start();
            }
        }

        protected override void OnExit(ExitEventArgs e)
        {
            _keyboardHook?.Uninstall();
            _watchdogService?.Stop();
            base.OnExit(e);
        }
    }
}
