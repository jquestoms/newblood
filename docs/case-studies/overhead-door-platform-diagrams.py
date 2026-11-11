#!/usr/bin/env python3
"""Four executive-scan diagrams for the Overhead Door platform case study, in the newblood
theme's .nb-diagram idiom (patterns.css): green strokes draw on when the parent .nb-reveal
becomes visible, text in the theme's text tokens, a wide and a portrait SVG per figure.
Inserts each after its section heading in docs/case-studies/overhead-door-platform.html."""
import pathlib, re

DRAFT = pathlib.Path('/Users/jeremyoms/Herd/newblood/docs/case-studies/overhead-door-platform.html')

def t(x, y, text, size=None, anchor='middle', d=0, cls='t', weight=None, extra=''):
    st = f'--d:{d}s' + (f';font-size:{size}px' if size else '') + (f';font-weight:{weight}' if weight else '')
    return f'<text class="{cls}{" " + extra if extra else ""}" style="{st}" x="{x}" y="{y}" text-anchor="{anchor}">{text}</text>'

def s(x, y, text, size=None, anchor='middle', d=0, extra=''):
    return t(x, y, text, size, anchor, d, cls='s', extra=extra)

def node(x, y, w, h, d=0, rx=10):
    return f'<rect class="node" style="--d:{d}s" x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}"/>'

def line(x1, y1, x2, y2, d=0, cls='d'):
    return f'<line class="{cls}" style="--d:{d}s" pathLength="100" x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}"/>'

def path(dd, d=0, cls='d'):
    return f'<path class="{cls}" style="--d:{d}s" pathLength="100" d="{dd}"/>'

def ah_right(x, y, d=0):   # arrowhead pointing right, tip at x
    return f'<path class="ah" style="--d:{d}s" d="M{x} {y} l-11-6 v12z"/>'

def ah_down(x, y, d=0):    # arrowhead pointing down, tip at y
    return f'<path class="ah" style="--d:{d}s" d="M{x} {y} l-6-11 h12z"/>'

def dot(x, y, r=7, d=0):
    return f'<circle class="dot" style="--d:{d}s" cx="{x}" cy="{y}" r="{r}"/>'

def svg(cls, w, h, body):
    return f'<svg class="{cls}" viewBox="0 0 {w} {h}" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">{"".join(body)}</svg>'

def block(name, wide, portrait, note=None):
    comment = f'\n  <!-- {note} -->' if note else ''
    return f'''{comment}
  <!-- wp:group {{"className":"nb-reveal","style":{{"spacing":{{"margin":{{"bottom":"var:preset|spacing|50"}}}}}},"layout":{{"type":"constrained"}}}} -->
  <div class="wp-block-group nb-reveal">
    <!-- wp:html -->
    <div class="nb-diagram nb-diagram-{name}">
      {wide}
      {portrait}
    </div>
    <!-- /wp:html -->
  </div>
  <!-- /wp:group -->
'''

# ---------------------------------------------------------------- A. stat tiles (commercial)
TILES = [('70%', ['of revenue is', 'commercial bid work']),
         ('8', ['commercial lines,', 'each its own page']),
         ('~700', ['spec sheets, drawings', 'and submittals']),
         ('1', ['bid form: specs, plans', 'and the due date'])]

def tiles_wide():
    b = []
    w, gap, x0, h = 220, 20, 10, 150
    for i, (num, lab) in enumerate(TILES):
        x = x0 + i * (w + gap); cx = x + w / 2; d = 0.1 + i * 0.25
        b.append(node(x, 4, w, h - 8, d))
        b.append(line(x + 24, 20, x + 72, 20, d + 0.1, 'd bar'))
        b.append(t(cx, 82, num, 48, d=d + 0.2))
        b.append(s(cx, 110, lab[0], d=d + 0.3)); b.append(s(cx, 130, lab[1], d=d + 0.3))
    return svg('nb-diagram--wide', 960, h, b)

def tiles_portrait():
    b = []
    w, h, gap = 150, 150, 12
    for i, (num, lab) in enumerate(TILES):
        col, row = i % 2, i // 2
        x = 4 + col * (w + gap); y = 4 + row * (h + gap); cx = x + w / 2; d = 0.1 + i * 0.25
        b.append(node(x, y, w, h, d))
        b.append(line(x + 20, y + 16, x + 60, y + 16, d + 0.1, 'd bar'))
        b.append(t(cx, y + 76, num, 42, d=d + 0.2))
        b.append(s(cx, y + 106, lab[0], 15, d=d + 0.3)); b.append(s(cx, y + 126, lab[1], 15, d=d + 0.3))
    return svg('nb-diagram--portrait', 320, 4 + 2 * (h + gap), b)

# ---------------------------------------------------------------- B. matrix (territory)
WORK = ['Commercial door repair', 'Rolling steel doors', 'Dock equipment', 'Fire door testing',
        'Garage door repair', 'Spring and opener repair']
WORK_SHORT = ['Door repair', 'Rolling steel', 'Dock', 'Fire test', 'Garage doors', 'Springs']
COUNTIES = ['Baltimore County', 'Baltimore City', 'Anne Arundel', 'Howard', 'Harford', 'Carroll',
            'Frederick', 'Cecil', 'Kent', "Queen Anne's", 'Talbot']
COUNTIES_SHORT = ['Baltimore Co.', 'Baltimore City', 'Anne Arundel', 'Howard', 'Harford', 'Carroll',
                  'Frederick', 'Cecil', 'Kent', "Queen Anne's", 'Talbot']
LIVE = {0}  # Baltimore County is the launch column

def ring(x, y, d):
    return f'<circle class="node" style="--d:{d}s" cx="{x}" cy="{y}" r="6"/>'

def matrix_wide():
    b = []
    left, top, cw, rh = 230, 118, 64, 36
    for j, c in enumerate(COUNTIES):
        x = left + j * cw + cw / 2
        b.append(f'<text class="s" style="--d:{0.1 + j * 0.05:.2f}s" x="{x}" y="{top - 14}" text-anchor="start" transform="rotate(-42 {x} {top - 14})">{c}</text>')
    for i, w in enumerate(WORK):
        y = top + i * rh + rh / 2
        b.append(s(left - 16, y + 5, w, anchor='end', d=0.2 + i * 0.08))
        b.append(line(left, top + i * rh, left + 11 * cw, top + i * rh, 0.1 + i * 0.05, 'd thin'))
        for j in range(11):
            x = left + j * cw + cw / 2; d = 0.6 + i * 0.08 + j * 0.05
            b.append(dot(x, y, 7, round(d, 2)) if j in LIVE else ring(x, y, round(d, 2)))
    b.append(line(left, top + 6 * rh, left + 11 * cw, top + 6 * rh, 0.5, 'd thin'))
    ly = top + 6 * rh + 40
    b.append(dot(left + 8, ly - 5, 7, 1.6)); b.append(s(left + 24, ly, 'live at launch', anchor='start', d=1.6))
    b.append(ring(left + 180, ly - 5, 1.7)); b.append(s(left + 196, ly, 'opened county by county as the search data earns them', anchor='start', d=1.7))
    return svg('nb-diagram--wide', 960, ly + 14, b)

def matrix_portrait():
    b = []
    left, top, cw, rh = 128, 112, 31, 28
    for j, w in enumerate(WORK_SHORT):
        x = left + j * cw + cw / 2
        b.append(f'<text class="s" style="--d:{0.1 + j * 0.05:.2f}s;font-size:15px" x="{x}" y="{top - 12}" text-anchor="start" transform="rotate(-60 {x} {top - 12})">{w}</text>')
    for i, c in enumerate(COUNTIES_SHORT):
        y = top + i * rh + rh / 2
        b.append(s(left - 10, y + 5, c, 15, anchor='end', d=0.2 + i * 0.05))
        b.append(line(left, top + i * rh, left + 6 * cw, top + i * rh, 0.1 + i * 0.04, 'd thin'))
        for j in range(6):
            x = left + j * cw + cw / 2; d = 0.6 + i * 0.05 + j * 0.06
            b.append(dot(x, y, 6, round(d, 2)) if i in LIVE else ring(x, y, round(d, 2)))
    b.append(line(left, top + 11 * rh, left + 6 * cw, top + 11 * rh, 0.5, 'd thin'))
    ly = top + 11 * rh + 34
    b.append(dot(16, ly - 5, 6, 1.6)); b.append(s(30, ly, 'live at launch', 15, anchor='start', d=1.6))
    b.append(ring(16, ly + 19, 1.7)); b.append(s(30, ly + 24, 'opened as the search data earns it', 15, anchor='start', d=1.7))
    return svg('nb-diagram--portrait', 320, ly + 38, b)

# ---------------------------------------------------------------- C. convergence (leads)
SOURCES = [('Phone call', 'tracked number, recorded'), ('Web form', 'saved before it is sent'), ('Chat', 'the assistant, after hours too')]
CARRIES = ['commercial, residential, service or parts', 'which form, chat or call it came by', 'the page and the search that started it']

def leads_wide():
    b = []
    nw, nh = 230, 60
    ys = [16, 100, 184]
    cx, cy, cw, ch = 400, 130, 170, 76
    for i, ((h, sub), y) in enumerate(zip(SOURCES, ys)):
        d = 0.1 + i * 0.2
        b.append(node(20, y, nw, nh, d)); b.append(t(20 + nw / 2, y + 27, h, d=d)); b.append(s(20 + nw / 2, y + 48, sub, d=d))
        my = y + nh / 2
        b.append(path(f'M{20 + nw} {my} C 320 {my}, 320 {cy}, {cx - 12} {cy}', d + 0.3))
    b.append(ah_right(cx, cy, 1.1))
    b.append(node(cx, cy - ch / 2, cw, ch, 0.9)); b.append(t(cx + cw / 2, cy - 4, 'One record', d=0.9)); b.append(s(cx + cw / 2, cy + 20, 'on the company’s own site', d=0.9))
    b.append(line(cx + cw, cy, 630, cy, 1.3)); b.append(ah_right(640, cy, 1.9))
    rx, rw, rh = 640, 300, 140
    b.append(node(rx, cy - rh / 2, rw, rh, 1.6)); b.append(t(rx + 20, cy - 38, 'Every lead carries', 18, 'start', 1.6))
    for i, c in enumerate(CARRIES):
        b.append(s(rx + 20, cy - 10 + i * 26, c, 15, 'start', 1.7 + i * 0.1))
    return svg('nb-diagram--wide', 960, 260, b)

def leads_portrait():
    b = []
    w, h, gap = 98, 58, 8
    short = [('Phone', 'recorded'), ('Form', 'saved first'), ('Chat', 'after hours')]
    cx, cy = 160, 166
    for i, (hh, sub) in enumerate(short):
        x = 4 + i * (w + gap); mx = x + w / 2; d = 0.1 + i * 0.2
        b.append(node(x, 4, w, h, d)); b.append(t(mx, 4 + 26, hh, 19, d=d)); b.append(s(mx, 4 + 47, sub, 14, d=d))
        b.append(path(f'M{mx} {4 + h} C {mx} 110, {cx} 100, {cx} {cy - 44}', d + 0.3))
    b.append(ah_down(cx, cy - 32, 1.1))
    b.append(node(60, cy - 32, 200, 64, 0.9)); b.append(t(cx, cy - 4, 'One record', d=0.9)); b.append(s(cx, cy + 18, 'on the company’s own site', 14, d=0.9))
    b.append(line(cx, cy + 32, cx, cy + 70, 1.3)); b.append(ah_down(cx, cy + 80, 1.9))
    ry = cy + 80
    b.append(node(8, ry, 304, 150, 1.6)); b.append(t(24, ry + 32, 'Every lead carries', 18, 'start', 1.6))
    lines = ['commercial, residential,', 'service or parts', 'which form, chat or call', 'the page and the search', 'that started the visit']
    for i, l in enumerate(lines):
        b.append(s(24, ry + 58 + i * 20, l, 14, 'start', 1.7 + i * 0.05))
    return svg('nb-diagram--portrait', 320, ry + 160, b)

# ---------------------------------------------------------------- D. ninety-day bar
def ninety():
    b = []
    y = 72
    x0, x30, x75, x90, xw = 40, 333, 773, 920, 1000
    b.append(line(x0, y, xw, y, 0, 'track'))
    segs = [(x0, x30, 0.2, 'Design', 'Design'), (x30, x75, 0.9, 'Build, review, approve', 'Build and review'),
            (x75, x90, 1.6, 'Run beside the old site', 'Run beside')]
    for a, bb, d, lab, short in segs:
        b.append(line(a, y, bb, y, d, 'd bar'))
        b.append(s((a + bb) / 2, y + 36, lab, d=d + 0.3, extra='nb-dg-lg'))
        b.append(s((a + bb) / 2, y + 36, short, d=d + 0.3, extra='nb-dg-sm'))
    b.append(line(x90, y, xw, y, 2.1, 'd thin'))
    b.append(s((x90 + xw) / 2, y + 60, 'watch, 2 weeks', d=2.3, extra='nb-dg-lg'))
    for x, d in ((x0, 0.2), (x30, 0.9), (x75, 1.6), (x90, 2.2)):
        b.append(dot(x, y, 7, d))
    marks = [(x0, 'Day 1', 'phones, email, ads untouched', 'start', 0), (x30, 'Day 30', 'design approved', 'middle', 0),
             (x75, 'Day 75', 'tests through the real routing', 'end', -10), (x90, 'Day 90', 'one switch', 'end', xw - x90)]
    for x, big, small, anc, dx in marks:
        d = 0.3 + (x - x0) / 440
        b.append(t(x + dx, y - 40, big, anchor=anc, d=d))
        b.append(s(x + dx, y - 18, small, anchor=anc, d=d, extra='nb-dg-lg'))
    return svg('', 1020, 150, b)

def ninety_block():
    return f'''
  <!-- wp:group {{"className":"nb-reveal","style":{{"spacing":{{"margin":{{"bottom":"var:preset|spacing|50"}}}}}},"layout":{{"type":"constrained"}}}} -->
  <div class="wp-block-group nb-reveal">
    <!-- wp:html -->
    <div class="nb-diagram nb-diagram-ninety">
      {ninety()}
    </div>
    <!-- /wp:html -->
  </div>
  <!-- /wp:group -->
'''

# ---------------------------------------------------------------- insert
SEO_NOTE = ('Six-month update (May 2027): add a before/after chart here, two bars per measure, baseline 11/2/2026 vs now: '
            'organic clicks a day, engaged sessions a weekday, brand position, calls a week; source docs/seo/reports in ohdbalt-build. '
            'Follow the dataviz skill; one hue, text in the text tokens.')

INSERTS = [
    ('<h2>Built for the commercial buyer</h2>\n  <!-- /wp:heading -->\n', block('ohd-tiles', tiles_wide(), tiles_portrait())),
    ('<h2>Owning the territory in search</h2>\n  <!-- /wp:heading -->\n', block('ohd-matrix', matrix_wide(), matrix_portrait(), SEO_NOTE)),
    ('<h2>Every lead, counted and kept</h2>\n  <!-- /wp:heading -->\n', block('ohd-leads', leads_wide(), leads_portrait())),
    ('<h2>Nothing stopped</h2>\n  <!-- /wp:heading -->\n', ninety_block()),
]

def main():
    html = DRAFT.read_text()
    html = re.sub(r'\n  <!-- wp:group \{"className":"nb-reveal".*?<!-- /wp:group -->\n', '\n', html, flags=re.S)  # idempotent
    html = re.sub(r'\n  <!-- Six-month update.*?-->\n', '\n', html, flags=re.S)
    for anchor, snippet in INSERTS:
        assert html.count(anchor) == 1, anchor
        html = html.replace(anchor, anchor + snippet)
    DRAFT.write_text(html)
    print('inserted 4 diagrams;', len(html), 'bytes; em dashes:', html.count('—'))

if __name__ == '__main__':
    main()
