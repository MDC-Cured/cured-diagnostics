from pathlib import Path
import zipfile

repo = Path(r'C:\Users\Pappi\Documents\GitHub\cured-diagnostics')
out = Path(r'C:\Users\Pappi\Documents\GitHub\chdfinal.zip')

if out.exists():
    out.unlink()

count = 0
with zipfile.ZipFile(out, 'w', compression=zipfile.ZIP_DEFLATED) as zf:
    for file in repo.rglob('*'):
        if not file.is_file():
            continue
        if '.git' in file.parts:
            continue
        if file.suffix.lower() == '.zip':
            continue
        arcname = Path(repo.name) / file.relative_to(repo)
        zf.write(file, arcname.as_posix())
        count += 1

print(f'Created {out}')
print(f'Files added: {count}')
