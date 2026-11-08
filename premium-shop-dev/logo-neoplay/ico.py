import struct, sys
files = [('out/final-%d.png' % s, s) for s in (16, 32, 48)]
data = [open(f, 'rb').read() for f, _ in files]
out = struct.pack('<HHH', 0, 1, len(files)); off = 6 + 16 * len(files)
for (f, s), d in zip(files, data):
    out += struct.pack('<BBBBHHII', s, s, 0, 0, 1, 32, len(d), off); off += len(d)
open('out/favicon.ico', 'wb').write(out + b''.join(data))
