using System;
using System.Windows;
using LibraryKiosk.Services;

namespace LibraryKiosk
{
    public partial class LoginWindow : Window
    {
        private readonly ApiService _apiService;
        private readonly string _terminalCode;
        private readonly System.Windows.Threading.DispatcherTimer _statusTimer;

        public LoginWindow(ApiService apiService, string terminalCode)
        {
            InitializeComponent();
            _apiService = apiService;
            _terminalCode = terminalCode;
            TerminalNameTxt.Text = $"Terminal '{terminalCode}' Active";

            // Initialize status timer
            _statusTimer = new System.Windows.Threading.DispatcherTimer
            {
                Interval = TimeSpan.FromSeconds(10)
            };
            _statusTimer.Tick += async (s, e) => await CheckConnection();
            _statusTimer.Start();
            
            // Initial check
            _ = CheckConnection();
        }

        private async System.Threading.Tasks.Task CheckConnection()
        {
            bool isAlive = await _apiService.PingAsync();
            if (isAlive)
            {
                StatusDot.Fill = new System.Windows.Media.SolidColorBrush(System.Windows.Media.Color.FromRgb(74, 222, 128)); // Green
                StatusTxt.Text = "CONNECTED TO SERVER";
                StatusTxt.Opacity = 1.0;
            }
            else
            {
                StatusDot.Fill = new System.Windows.Media.SolidColorBrush(System.Windows.Media.Color.FromRgb(248, 113, 113)); // Red
                StatusTxt.Text = "SERVER OFFLINE";
                StatusTxt.Opacity = 0.8;
            }
        }

        private async void LoginBtn_Click(object sender, RoutedEventArgs e)
        {
            string userId = UserIdInput.Text;
            string password = PasswordInput.Password;

            if (string.IsNullOrWhiteSpace(userId) || string.IsNullOrWhiteSpace(password))
            {
                ShowError("Please fill in all fields.");
                return;
            }

            LoginBtn.IsEnabled = false;
            LoginBtn.Content = "Signing in...";
            ErrorMsg.Visibility = Visibility.Collapsed;

            var response = await _apiService.LoginAsync(userId, password);

            if (response.Success)
            {
                // Navigate to Session Bar
                var sessionBar = new SessionBarWindow(response, _apiService);
                sessionBar.Show();
                this.Hide();
            }
            else
            {
                ShowError(response.Error ?? "Invalid credentials.");
            }

            LoginBtn.IsEnabled = true;
            LoginBtn.Content = "Login";
        }

        private void PasswordInput_KeyDown(object sender, System.Windows.Input.KeyEventArgs e)
        {
            if (e.Key == System.Windows.Input.Key.Enter)
            {
                LoginBtn_Click(sender, e);
            }
        }

        private void ShowError(string msg)
        {
            ErrorMsg.Text = msg;
            ErrorMsg.Visibility = Visibility.Visible;
        }
    }
}
