using System;
using System.Net.Http;
using System.Net.Http.Json;
using System.Text.Json;
using System.Threading.Tasks;
using LibraryKiosk.Models;

namespace LibraryKiosk.Services
{
    public class ApiService
    {
        private readonly HttpClient _httpClient;
        private readonly string _apiBaseUrl;
        private readonly string _terminalCode;

        public ApiService(string apiBaseUrl, string terminalCode)
        {
            _httpClient = new HttpClient();
            _apiBaseUrl = apiBaseUrl.TrimEnd('/');
            _terminalCode = terminalCode;
        }

        public async Task<ApiResponse<UserInfo>> LoginAsync(string userId, string password)
        {
            var request = new LoginRequest
            {
                UserId = userId,
                Password = password,
                TerminalCode = _terminalCode
            };

            try
            {
                var response = await _httpClient.PostAsJsonAsync($"{_apiBaseUrl}/login.php", request);
                var content = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize<ApiResponse<UserInfo>>(content) ?? new ApiResponse<UserInfo> { Success = false, Error = "Deserialization failed" };
            }
            catch (Exception ex)
            {
                return new ApiResponse<UserInfo> { Success = false, Error = $"Network error: {ex.Message}" };
            }
        }

        public async Task<bool> HeartbeatAsync(string token)
        {
            try
            {
                var request = new HttpRequestMessage(HttpMethod.Post, $"{_apiBaseUrl}/heartbeat.php");
                request.Headers.Add("X-Session-Token", token);
                
                var response = await _httpClient.SendAsync(request);
                return response.IsSuccessStatusCode;
            }
            catch
            {
                return false;
            }
        }

        public async Task LogoutAsync(string token)
        {
            try
            {
                var request = new HttpRequestMessage(HttpMethod.Post, $"{_apiBaseUrl}/logout.php");
                request.Headers.Add("X-Session-Token", token);
                await _httpClient.SendAsync(request);
            }
            catch { /* Ignored on logout */ }
        }
    }
}
