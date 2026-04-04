<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — ISGH</title>
<style>
  body { font-family: system-ui, sans-serif; background: #f3f4f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
  .card { background: #fff; border-radius: 12px; padding: 2rem 2.5rem; box-shadow: 0 4px 24px rgba(0,0,0,0.1); width: 100%; max-width: 360px; }
  h2 { margin: 0 0 1.5rem; color: #1a4a2e; font-size: 1.25rem; }
  input[type=password] { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; margin-bottom: 1rem; }
  button { width: 100%; padding: 10px; background: #1a4a2e; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
  .error { color: #dc2626; font-size: 13px; margin-bottom: 1rem; }
</style>
</head>
<body>
<div class="card">
  <h2>🔐 ISGH Admin Panel</h2>
  @if ($errors->any())
    <div class="error">{{ $errors->first() }}</div>
  @endif
  <form method="POST" action="{{ route('admin.login') }}">
    @csrf
    <input type="password" name="admin_token" placeholder="Enter admin token" autofocus required>
    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>
