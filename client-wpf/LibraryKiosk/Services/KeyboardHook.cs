using System;
using System.Diagnostics;
using System.Runtime.InteropServices;
using System.Windows.Input;
using LibraryKiosk.Infrastructure;

namespace LibraryKiosk.Services
{
    public class KeyboardHook : IDisposable
    {
        private NativeMethods.LowLevelKeyboardProc _proc;
        private IntPtr _hookId = IntPtr.Zero;

        public KeyboardHook()
        {
            _proc = HookCallback;
        }

        public void Install()
        {
            _hookId = SetHook(_proc);
        }

        public void Uninstall()
        {
            if (_hookId != IntPtr.Zero)
            {
                NativeMethods.UnhookWindowsHookEx(_hookId);
                _hookId = IntPtr.Zero;
            }
        }

        private IntPtr SetHook(NativeMethods.LowLevelKeyboardProc proc)
        {
            using (Process curProcess = Process.GetCurrentProcess())
            using (ProcessModule curModule = curProcess.MainModule!)
            {
                return NativeMethods.SetWindowsHookEx(NativeMethods.WH_KEYBOARD_LL, proc,
                    NativeMethods.GetModuleHandle(curModule.ModuleName!), 0);
            }
        }

        private IntPtr HookCallback(int nCode, IntPtr wParam, IntPtr lParam)
        {
            if (nCode >= 0 && (wParam == (IntPtr)NativeMethods.WM_KEYDOWN || wParam == (IntPtr)NativeMethods.WM_SYSKEYDOWN))
            {
                int vkCode = Marshal.ReadInt32(lParam);
                Key key = KeyInterop.KeyFromVirtualKey(vkCode);

                if (IsDisallowed(key))
                {
                    Debug.WriteLine($"Blocked key: {key}");
                    return (IntPtr)1; // Block the key
                }
            }
            return NativeMethods.CallNextHookEx(_hookId, nCode, wParam, lParam);
        }

        private bool IsDisallowed(Key key)
        {
            // Block Alt+Tab
            if (Keyboard.Modifiers.HasFlag(ModifierKeys.Alt) && key == Key.Tab) return true;
            
            // Block Alt+F4
            if (Keyboard.Modifiers.HasFlag(ModifierKeys.Alt) && key == Key.F4) return true;

            // Block Win Keys
            if (key == Key.LWin || key == Key.RWin) return true;

            // Block Ctrl+Esc (Start menu)
            if (Keyboard.Modifiers.HasFlag(ModifierKeys.Control) && key == Key.Escape) return true;

            // Block Ctrl+Shift+Esc (Task Manager shortcut)
            if (Keyboard.Modifiers.HasFlag(ModifierKeys.Control) && Keyboard.Modifiers.HasFlag(ModifierKeys.Shift) && key == Key.Escape) return true;

            return false;
        }

        public void Dispose()
        {
            Uninstall();
        }
    }
}
