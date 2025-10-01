# 🔧 REVERB REAL-TIME TROUBLESHOOTING GUIDE

## 🚨 Current Issue: Chat Not Real-Time

The chat feature is not working in real-time. Let's troubleshoot step by step.

## ✅ What's Been Verified

1. **Laravel Reverb Installed**: ✅ `laravel/reverb: ^1.6.0`
2. **Broadcasting Config**: ✅ `BROADCAST_DRIVER=reverb`
3. **Reverb Config**: ✅ Server configuration exists
4. **Environment Variables**: ✅ `REVERB_APP_KEY`, `VITE_REVERB_APP_KEY` set
5. **Events Configured**: ✅ `MessageSent` and `UserTyping` events
6. **Controllers Broadcasting**: ✅ Events dispatched in controllers
7. **Channel Authorization**: ✅ `routes/channels.php` configured

## 🔍 Debugging Steps

### Step 1: Check Reverb Server Status
```bash
# Check if Reverb server is running
php artisan reverb:start --host=127.0.0.1 --port=8080
```

### Step 2: Check Frontend Connection
1. Open browser console
2. Look for these messages:
   - `"Initializing Reverb with credentials"`
   - `"✅ Reverb connected successfully!"`
   - `"❌ Reverb connection error:"`

### Step 3: Test Broadcasting
1. Visit `/test-reverb` (while logged in)
2. Check browser console for received messages
3. Check Laravel logs for broadcasting events

### Step 4: Check Environment Variables
```bash
php artisan tinker --execute="echo 'VITE_REVERB_APP_KEY: ' . env('VITE_REVERB_APP_KEY');"
```

## 🚨 Common Issues & Solutions

### Issue 1: Reverb Server Not Running
**Symptoms**: No connection logs in browser console
**Solution**: 
```bash
php artisan reverb:start --host=127.0.0.1 --port=8080
```

### Issue 2: Frontend Using Mock Echo
**Symptoms**: Console shows `"Mock: Listening to..."`
**Solution**: 
1. Check `VITE_REVERB_APP_KEY` is set
2. Run `npm run build`
3. Clear browser cache

### Issue 3: Channel Authorization Failed
**Symptoms**: 403 errors in browser console
**Solution**:
1. Ensure user is logged in
2. Check conversation exists
3. Verify user is conversation participant

### Issue 4: Events Not Broadcasting
**Symptoms**: No events in Laravel logs
**Solution**:
1. Check `BROADCAST_DRIVER=reverb` in .env
2. Run `php artisan config:clear`
3. Verify Reverb server is running

## 🧪 Testing Commands

### Test Reverb Connection
```bash
# Start Reverb server
php artisan reverb:start --host=127.0.0.1 --port=8080

# Test broadcasting (in another terminal)
curl -X GET "http://localhost:8000/test-reverb" \
  -H "Cookie: laravel_session=your_session_cookie"
```

### Test Frontend Connection
```javascript
// In browser console
console.log('Echo:', window.Echo);
console.log('Connected:', window.Echo.connector.socket.connected);
console.log('Socket:', window.Echo.connector.socket);
```

### Test Channel Subscription
```javascript
// In browser console
const channel = window.Echo.private('conversation.1');
channel.listen('message.sent', (e) => {
    console.log('Received message:', e);
});
```

## 🔧 Quick Fixes

### Fix 1: Restart Everything
```bash
# Stop Reverb server (Ctrl+C)
# Clear config cache
php artisan config:clear
php artisan config:cache

# Rebuild frontend
npm run build

# Start Reverb server
php artisan reverb:start --host=127.0.0.1 --port=8080
```

### Fix 2: Check Environment Variables
```env
# Add to .env file
BROADCAST_DRIVER=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=dwtkrzfyb53ym2js3hqt
REVERB_APP_SECRET=or6uormm40v4bddrzqvs
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Fix 3: Force Frontend Rebuild
```bash
# Clear node modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

## 🎯 Expected Behavior

### When Working Correctly:
1. **Reverb Server**: Shows connection logs
2. **Browser Console**: Shows `"✅ Reverb connected successfully!"`
3. **Message Sending**: Messages appear instantly on both ends
4. **Typing Indicators**: Animated dots show when typing
5. **Test Route**: `/test-reverb` broadcasts test message

### Debug Output:
```
Reverb Server: [2024-01-01 12:00:00] Connection established
Browser Console: ✅ Reverb connected successfully!
Laravel Logs: Broadcasting [App\Events\MessageSent] on channels [conversation.1]
```

## 🚀 Next Steps

1. **Start Reverb Server**: `php artisan reverb:start`
2. **Check Browser Console**: Look for connection messages
3. **Test Broadcasting**: Visit `/test-reverb`
4. **Check Real Messaging**: Send messages between staff/resident
5. **Report Results**: Share console output and any errors

## 📞 Need Help?

If still not working, please share:
1. Browser console output
2. Reverb server logs
3. Laravel logs
4. Environment variables (without secrets)
5. Steps you've tried

The issue is likely one of:
- Reverb server not running
- Frontend not connecting to Reverb
- Environment variables not set correctly
- Channel authorization failing


