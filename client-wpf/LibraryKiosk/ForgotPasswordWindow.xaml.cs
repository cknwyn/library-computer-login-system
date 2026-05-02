using System;
using System.Windows;
using LibraryKiosk.Services;

namespace LibraryKiosk
{
    public partial class ForgotPasswordWindow : Window
    {
        private readonly ApiService _apiService;
        private int _currentStep = 1; // 1: Identify, 2: Verify, 3: Reset
        private string _targetUserId = "";

        public ForgotPasswordWindow(ApiService apiService)
        {
            InitializeComponent();
            _apiService = apiService;
        }

        private void CancelBtn_Click(object sender, RoutedEventArgs e)
        {
            Close();
        }

        private async void ActionBtn_Click(object sender, RoutedEventArgs e)
        {
            ErrorMsg.Visibility = Visibility.Collapsed;
            ActionBtn.IsEnabled = false;

            try {
                if (_currentStep == 1)
                {
                    await HandleStepIdentify();
                }
                else if (_currentStep == 2)
                {
                    HandleStepVerify();
                }
                else if (_currentStep == 3)
                {
                    await HandleStepReset();
                }
            }
            finally {
                ActionBtn.IsEnabled = true;
            }
        }

        private async System.Threading.Tasks.Task HandleStepIdentify()
        {
            string userId = UserIdInput.Text.Trim();
            if (string.IsNullOrEmpty(userId)) {
                ShowError("Please enter your Student/Staff ID.");
                return;
            }

            var result = await _apiService.RequestPasswordResetAsync(userId);
            if (result.Success)
            {
                _targetUserId = userId;
                _currentStep = 2;
                IdentifyPanel.Visibility = Visibility.Collapsed;
                VerifyPanel.Visibility = Visibility.Visible;
                TitleTxt.Text = "Verify Code";
                StepDesc.Text = "Enter the code sent to your email";
                ActionBtn.Content = "Next";
            }
            else
            {
                ShowError(result.Error ?? "Failed to request reset.");
            }
        }

        private void HandleStepVerify()
        {
            string code = CodeInput.Text.Trim();
            if (code.Length != 6) {
                ShowError("Please enter the 6-digit code.");
                return;
            }

            // In Step 2 we just proceed to password input locally
            _currentStep = 3;
            VerifyPanel.Visibility = Visibility.Collapsed;
            ResetPanel.Visibility = Visibility.Visible;
            TitleTxt.Text = "Set Password";
            StepDesc.Text = "Choose a new strong password";
            ActionBtn.Content = "Reset Password";
        }

        private async System.Threading.Tasks.Task HandleStepReset()
        {
            string pass = NewPasswordInput.Password;
            string confirm = ConfirmPasswordInput.Password;
            string code = CodeInput.Text.Trim();

            if (pass.Length < 6) {
                ShowError("Password must be at least 6 characters.");
                return;
            }
            if (pass != confirm) {
                ShowError("Passwords do not match.");
                return;
            }

            var result = await _apiService.ResetPasswordAsync(_targetUserId, code, pass);
            if (result.Success)
            {
                System.Windows.MessageBox.Show("Password reset successful! You can now log in.", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                Close();
            }
            else
            {
                ShowError(result.Error ?? "Failed to reset password.");
            }
        }

        private void ShowError(string msg)
        {
            ErrorMsg.Text = msg;
            ErrorMsg.Visibility = Visibility.Visible;
        }
    }
}
