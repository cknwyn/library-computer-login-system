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
        public string TerminalCode { get; }
        public string PcName { get; }

        public ApiService(string apiBaseUrl, string terminalCode, string pcName)
        {
            _httpClient = new HttpClient();
            _apiBaseUrl = apiBaseUrl.TrimEnd('/');
            TerminalCode = terminalCode;
            PcName = pcName;
        }

        public async Task<bool> PingAsync()
        {
            try
            {
                var response = await _httpClient.GetAsync($"{_apiBaseUrl}/ping.php");
                return response.IsSuccessStatusCode;
            }
            catch { return false; }
        }

        public async Task<ApiResponse<UserInfo>> LoginAsync(string userId, string password)
        {
            var request = new LoginRequest
            {
                UserId = userId,
                Password = password,
                TerminalCode = TerminalCode,
                PcName = PcName
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

        public async Task<string> TrackWebsiteAsync(string token, string url, string title)
        {
            var requestData = new { url = url, title = title };
            try
            {
                var request = new HttpRequestMessage(HttpMethod.Post, $"{_apiBaseUrl}/track_website.php");
                request.Headers.Add("X-Session-Token", token);
                request.Content = JsonContent.Create(requestData);
                
                var response = await _httpClient.SendAsync(request);
                return await response.Content.ReadAsStringAsync();
            }
            catch (Exception ex)
            {
                return $"{{\"success\":false,\"error\":\"{ex.Message}\"}}";
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
