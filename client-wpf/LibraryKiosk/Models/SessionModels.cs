using System;
using System.Text.Json.Serialization;

namespace LibraryKiosk.Models
{
    public class LoginRequest
    {
        [JsonPropertyName("user_id")]
        public string UserId { get; set; } = string.Empty;

        [JsonPropertyName("password")]
        public string Password { get; set; } = string.Empty;

        [JsonPropertyName("terminal_code")]
        public string TerminalCode { get; set; } = string.Empty;
    }

    public class ApiResponse<T>
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("error")]
        public string? Error { get; set; }

        [JsonPropertyName("user")]
        public UserInfo? User { get; set; }

        [JsonPropertyName("session_id")]
        public int SessionId { get; set; }

        [JsonPropertyName("session_token")]
        public string? SessionToken { get; set; }

        [JsonPropertyName("login_time")]
        public string? LoginTime { get; set; }
    }

    public class UserInfo
    {
        [JsonPropertyName("id")]
        public string Id { get; set; } = string.Empty;

        [JsonPropertyName("full_name")]
        public string FullName { get; set; } = string.Empty;

        [JsonPropertyName("role")]
        public string Role { get; set; } = string.Empty;
    }
}
