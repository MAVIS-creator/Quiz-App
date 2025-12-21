# Local PeerJS Server Setup Guide

Since you've already installed PeerJS globally with `npm install -g peer`, you can now set up a local server for much faster and more reliable connections.

## Why Local PeerJS Server?

- **Speed**: Direct connection to your server instead of routing through PeerJS cloud (~23 seconds → ~1-2 seconds)
- **Reliability**: No dependency on external cloud services
- **Privacy**: All connections stay within your network
- **Control**: You manage the server and infrastructure

## Quick Start

### 1. Start the PeerJS Server

Open PowerShell/Terminal and run:

```powershell
peerjs --port 9000 --key peerjs
```

You should see:
```
PeerServer started on port 9000
```

**Keep this terminal window open** while using the quiz system.

### 2. Get Your Computer's IP Address

In PowerShell, run:

```powershell
ipconfig
```

Look for "IPv4 Address" under your network adapter (usually something like `192.168.x.x`)

**Example**: `192.168.1.100`

### 3. Update Quiz Files

You need to update **two files** to point to your local server:

#### File 1: `quiz_new.php`

Find this line (around line 515):
```javascript
peer = new Peer('student_' + identifier);
```

Replace with:
```javascript
peer = new Peer('student_' + identifier, {
    host: '192.168.1.100',    // Replace with YOUR IP from step 2
    port: 9000,
    path: '/',
    secure: false,
    config: { iceServers: [{ urls: ['stun:stun.l.google.com:19302'] }] }
});
```

#### File 2: `proctor.php`

Find this line (around line 440):
```javascript
peer = new Peer('proctor_' + Date.now());
```

Replace with:
```javascript
peer = new Peer('proctor_' + Date.now(), {
    host: '192.168.1.100',    // Replace with YOUR IP from step 2
    port: 9000,
    path: '/',
    secure: false,
    config: { iceServers: [{ urls: ['stun:stun.l.google.com:19302'] }] }
});
```

### 4. Test It

1. Start the PeerJS server (step 1)
2. Refresh your browser (localhost)
3. Open test page: `test_peerjs_video.html`
4. Click "Start Broadcasting" and "View Live"
5. **Connection should be MUCH faster** (1-2 seconds instead of 23 seconds)

## For Production (School Network)

If you deploy to a school network, you'll need:

### Option A: Same Network (WiFi)
- Run PeerJS server on a laptop/PC on the school network
- Students connect via the laptop's IP address
- Works great for exams in same building

### Option B: Public Server
Replace `192.168.1.100` with your actual domain/public IP:

```javascript
peer = new Peer('student_' + identifier, {
    host: 'your-domain.com',  // or your public IP
    port: 9000,
    path: '/',
    secure: true,              // Use HTTPS in production
    config: { iceServers: [{ urls: ['stun:stun.l.google.com:19302'] }] }
});
```

## Troubleshooting

### "Connection refused" or "Cannot reach server"
- Make sure PeerJS server is running (check terminal)
- Check IP address is correct (run `ipconfig` again)
- Make sure port 9000 is not blocked by firewall

### Still slow (5+ seconds)
- Your network has "Symmetric NAT" (blocks P2P)
- You'll need a TURN server (more complex setup)
- Contact your network administrator

### "Secure requires TLS" error
- You're using `secure: true` over HTTP
- Either use `secure: false` (localhost) or set up HTTPS

## Advanced: Custom Configuration

```javascript
peer = new Peer('student_' + identifier, {
    host: '192.168.1.100',
    port: 9000,
    path: '/',
    secure: false,
    config: {
        iceServers: [
            { urls: ['stun:stun.l.google.com:19302'] },
            { urls: ['stun:stun1.l.google.com:19302'] }
        ]
    }
});
```

## Keep the Server Running

During exams, the PeerJS server must be running. Options:

1. **Development**: Keep terminal open
2. **Production**: Use PM2 (process manager)
   ```powershell
   npm install -g pm2
   pm2 start "peerjs --port 9000 --key peerjs"
   pm2 save
   pm2 startup
   ```

3. **Windows Service**: Use NSSM (Non-Sucking Service Manager)

## Summary

- **Local Server**: 1-2 seconds connection time ✅
- **PeerJS Cloud**: 20-30 seconds connection time ❌
- **Setup Time**: 5 minutes
- **Difficulty**: Easy

This is the recommended setup for reliable high-speed live video proctoring!
