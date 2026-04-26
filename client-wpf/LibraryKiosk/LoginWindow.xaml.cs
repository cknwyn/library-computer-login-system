using System;
using System.Windows;
using LibraryKiosk.Services;

namespace LibraryKiosk
{
    public partial class LoginWindow : Window
    {
        private readonly ApiService _apiService;
        private readonly string _terminalCode;

        public LoginWindow(ApiService apiService, string terminalCode)
        {
            InitializeComponent();
            _apiService = apiService;
            _terminalCode = terminalCode;
            TerminalNameTxt.Text = $"Terminal '{terminalCode}' Active";
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
