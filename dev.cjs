const { spawn } = require('child_process');

console.log('[Dev Server] Starting MariaDB service check...');
try {
  spawn('sh', ['-c', 'mysqladmin ping >/dev/null 2>&1 || (mkdir -p /var/run/mysqld && chown -R mysql:mysql /var/run/mysqld /var/lib/mysql && mariadbd --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 &)'], {
    detached: true,
    stdio: 'ignore'
  }).unref();
} catch (e) {
  console.error('[Dev Server] MariaDB start check error:', e);
}

console.log('[Dev Server] Starting Plain PHP 8.x server on 0.0.0.0:3000...');
const php = spawn('php', ['-S', '0.0.0.0:3000', '-t', 'public', 'router.php'], {
  stdio: 'inherit',
  env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' }
});

php.on('error', (err) => {
  console.error('[Dev Server] Failed to start PHP server:', err);
});

php.on('exit', (code, signal) => {
  console.log(`[Dev Server] PHP server exited with code ${code} and signal ${signal}`);
  process.exit(code || 0);
});

process.on('SIGTERM', () => php.kill('SIGTERM'));
process.on('SIGINT', () => php.kill('SIGINT'));
