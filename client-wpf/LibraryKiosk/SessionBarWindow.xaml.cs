using System;
using System.Windows;
using System.Windows.Threading;
using System.Windows.Forms;
using LibraryKiosk.Models;
using LibraryKiosk.Services;
using Application = System.Windows.Application;

namespace LibraryKiosk
{
    public partial class SessionBarWindow : Window
    {
        private readonly ApiResponse<UserInfo> _session;
        private readonly ApiService _apiService;
        private readonly DispatcherTimer _heartbeatTimer;
        private NotifyIcon? _notifyIcon;

        public SessionBarWindow(ApiResponse<UserInfo> session, ApiService apiService)
        {
            InitializeComponent();
            _session = session;
            _apiService = apiService;

            TerminalText.Text = $"Terminal {_session.SessionId.ToString("D3")}";
            UserText.Text = $"Active User: {_session.User?.FullName ?? "Unknown"}";

            // Position bottom right
            this.Left = SystemParameters.WorkArea.Width - this.Width - 10;
            this.Top = SystemParameters.WorkArea.Height - this.Height - 10;

            // Heartbeat
            _heartbeatTimer = new DispatcherTimer
            {
                Interval = TimeSpan.FromSeconds(30)
            };
            _heartbeatTimer.Tick += async (s, e) => await DoHeartbeat();
            _heartbeatTimer.Start();

            SetupTrayIcon();
        }

        private async Task DoHeartbeat()
        {
            if (_session.SessionToken != null)
            {
                bool success = await _apiService.HeartbeatAsync(_session.SessionToken);
                if (!success)
                {
                    // Maybe connection lost?
                }
            }
        }

        private void SetupTrayIcon()
        {
            _notifyIcon = new NotifyIcon
            {
                Icon = System.Drawing.SystemIcons.Information,
                Visible = true,
                Text = "Library Session Active"
            };
            _notifyIcon.DoubleClick += (s, e) => { this.Show(); this.WindowState = WindowState.Normal; };
            
            // Context menu for the tray icon
            var contextMenu = new ContextMenuStrip();
            contextMenu.Items.Add("Show Session Bar", null, (s, e) => { this.Show(); this.WindowState = WindowState.Normal; });
            contextMenu.Items.Add("End Session", null, (s, e) => Settings_Click(s, new RoutedEventArgs()));
            _notifyIcon.ContextMenuStrip = contextMenu;
        }

        private async void Settings_Click(object sender, RoutedEventArgs e)
        {
            // Simple minimize logic for right-click or button
            if (sender is Button)
            {
                this.Hide();
                return;
            }

            var result = System.Windows.MessageBox.Show("Are you sure you want to end your session?", 
                "End Session", MessageBoxButton.YesNo, MessageBoxImage.Question);
            
            if (result == MessageBoxResult.Yes)
            {
                if (_session.SessionToken != null)
                {
                    await _apiService.LogoutAsync(_session.SessionToken);
                }
                
                _heartbeatTimer.Stop();
                _notifyIcon?.Dispose();
                
                // Return to login
                var login = new LoginWindow();
                login.Show();
                this.Close();
            }
        }
    }
}
