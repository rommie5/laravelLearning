const { exec } = require('child_process');
exec('npx vite build', (err, stdout, stderr) => {
    const fs = require('fs');
    fs.writeFileSync('clean_err.txt', (err ? err.message + '\n' : '') + stdout + '\n' + stderr);
});
