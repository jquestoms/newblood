# Inland Trades Prospecting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give Claude a repeatable assistant procedure, plus a small deterministic scorer, for sweeping one trade category on Google Maps near Loma Linda and handing Jeremy a ranked, contactable prospect batch, then run it for real on HVAC batch 1.

**Architecture:** The judgment work (browsing Maps and websites, spotting community ties, drafting emails) is done by Claude in Jeremy's Chrome, following a written playbook. Observations go into one JSON file per batch; a stdlib-only Python scorer validates them, applies the spec's 100-point rubric, skips and exclusivity flags, and renders the score blocks and the "everyone else" table as Markdown. Claude assembles the final batch file from that output plus the hand-written way-in lines and email drafts.

**Tech Stack:** Python 3.9 standard library only (`json`, `unittest`, `argparse`, `re`); Claude in Chrome for browsing; the New Blood CRM CLI for skip checks and the handoff.

**Spec:** `docs/superpowers/specs/2026-10-07-inland-trades-prospecting-design.md`

## Global Constraints

- Python 3.9.6, stdlib only (no pytest, no pip installs). Tests run with `python3 -m unittest`. No `match`, no `X | Y` type syntax.
- All paths under `~/Herd/newblood/prospects/inland-trades/`. Prospect names never go to artifacts or external services: batch JSON and batch Markdown are git-ignored.
- Scoring is out of 100: Fit 35 (owner-run 10, 10+ years 10, high job value 10, commercial 5) · Opportunity 30 (undersold reputation 15 = 4.7+ stars and 100+ reviews on a site with gaps; web gaps 10; no website 5) · Way in 25 (community tie 12, brand/dealer peer 8, timely moment 5) · Reachability 10 (named person's email 10, generic 6, form/phone only 2).
- Automatic skips: `in_crm`, `client`, `franchise`. Same-trade live CRM deal → flag "conflicts with <deal> Territory", not dropped.
- Never guess an email: every email carries the source it was found on.
- ~40 businesses per session max; new Chrome tab; no logins, form submissions or messages; stop on captcha/block.
- Email drafts: open on the familiar connection; no em dashes; no pricing; nothing negative about their site; one soft yes/no ask; signature with New Blood's mailing address and an easy out; printed in a plain fenced code block.
- CRM writes are propose → Jeremy's yes → run → report the output line. Claude never logs a send until Jeremy says it went.
- The newblood repo is on branch `feature/redesign`; commit only this plan's files, never sweep in other uncommitted changes (`git add <exact paths>`).

## Review Focus

1. **An email with no recorded source** (a guess) → the scorer refuses the batch with a clear error naming the business, so a guessed address can never reach a draft.
2. **No website but a strong reputation** (4.8 stars, 300 reviews, Facebook only) → Opportunity is 5, not 15 + 5; the "undersold" points need a site with gaps.
3. **A brand-new listing with no rating or review count** → it scores without crashing; undersold is simply false.
4. **The same business listed twice** (two Maps pins, same phone) → scored once, with a warning in the output naming the duplicate.
5. **Exclusivity with mismatched casing or spacing** (category "hvac " vs conflict trade "HVAC") → the flag still applies.

Each of these has a test in Task 1.

---

## File Structure

```
prospects/inland-trades/
  README.md            playbook: the session procedure Claude follows (Task 3)
  tracker.md           one row per category, order, re-sweep dates (Task 3)
  email-rules.md       first-touch email rules + signature block (Task 3)
  batch-template.md    the batch file skeleton (Task 3)
  .gitignore           ignores batches/ and dated batch .md files (Task 1)
  batches/             <date>-<category>.json observations (git-ignored)
  <date>-<category>.md the batch handed to Jeremy (git-ignored)
  tools/
    score.py           validate + score + rank + render (Tasks 1-2)
    test_score.py      unittest suite (Tasks 1-2)
```

---

### Task 1: Scorer core (validation, scoring, skips, dedupe, exclusivity)

**Files:**
- Create: `prospects/inland-trades/tools/score.py`
- Create: `prospects/inland-trades/tools/test_score.py`
- Create: `prospects/inland-trades/.gitignore`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `class BatchError(ValueError)`
  - `validate_business(b: dict) -> None` (raises `BatchError`)
  - `score_business(b: dict) -> dict` returning `{"fit": int, "opportunity": int, "way_in": int, "reach": int, "total": int, "reasons": {"fit": [str], "opportunity": [str], "way_in": [str], "reach": [str]}}`
  - `rank(batch: dict) -> dict` returning `{"ranked": [{"business": dict, "score": dict, "flags": [str]}], "skipped": [{"business": dict, "reason": str}], "warnings": [str]}`, ranked by total desc, then reviews desc (None as 0), then name.

Business record fields (all keys optional except `name`): `name` str · `city` str · `maps_category` str · `rating` float|None · `reviews` int|None · `website` str|None · `phone` str|None · `skip` None|"in_crm"|"client"|"franchise" · `owner_run` bool · `years` int|None · `high_job_value` bool · `commercial` bool · `web_gaps` [str] · `community_ties` [{"text","source"}] · `brand_peers` [{"text","source"}] · `timely` [{"text","source"}] · `owner` str|None · `email` str|None · `email_kind` None|"person"|"generic" · `email_source` str|None · `notes` str.

Batch fields: `category` str · `search` str · `date` "YYYY-MM-DD" · `conflict_deals` [{"trade": str, "deal": str}] · `businesses` [business].

- [ ] **Step 1: Create the folder and .gitignore**

```bash
mkdir -p ~/Herd/newblood/prospects/inland-trades/tools ~/Herd/newblood/prospects/inland-trades/batches
```

`prospects/inland-trades/.gitignore`:
```
# Prospect names stay local (spec: never to external services)
batches/
20*.md
```

- [ ] **Step 2: Write the failing tests**

`prospects/inland-trades/tools/test_score.py`:
```python
import unittest

from score import BatchError, rank, score_business, validate_business


def biz(**kw):
    b = {
        "name": "Acme Heating", "city": "Redlands", "rating": 4.9, "reviews": 250,
        "website": "https://acme.example", "phone": "(909) 555-0100", "skip": None,
        "owner_run": True, "years": 30, "high_job_value": True, "commercial": True,
        "web_gaps": ["broken city links"], "community_ties": [], "brand_peers": [],
        "timely": [], "email": None, "email_kind": None, "email_source": None,
    }
    b.update(kw)
    return b


class ScoreTests(unittest.TestCase):
    def test_full_marks(self):
        s = score_business(biz(
            community_ties=[{"text": "sponsors LLUC soccer", "source": "https://acme.example/about"}],
            brand_peers=[{"text": "Trane dealer", "source": "https://acme.example"}],
            timely=[{"text": "new Colton shop", "source": "https://acme.example"}],
            email="nick@acme.example", email_kind="person", email_source="contact page",
        ))
        self.assertEqual((s["fit"], s["opportunity"], s["way_in"], s["reach"], s["total"]),
                         (35, 25, 25, 10, 95))

    def test_opportunity_max_is_30_without_website_points(self):
        # undersold 15 + gaps 10; no-website 5 cannot combine with them
        s = score_business(biz())
        self.assertEqual(s["opportunity"], 25)

    def test_no_website_strong_reviews_gets_only_5(self):
        s = score_business(biz(website=None, web_gaps=[], rating=4.8, reviews=300))
        self.assertEqual(s["opportunity"], 5)

    def test_missing_rating_and_reviews_scores(self):
        s = score_business(biz(rating=None, reviews=None))
        self.assertEqual(s["opportunity"], 10)  # gaps only, not undersold

    def test_young_business_no_years_points(self):
        self.assertEqual(score_business(biz(years=6))["fit"], 25)
        self.assertEqual(score_business(biz(years=None))["fit"], 25)

    def test_reach_levels(self):
        self.assertEqual(score_business(biz())["reach"], 2)
        self.assertEqual(score_business(biz(email="info@a.example", email_kind="generic",
                                            email_source="footer"))["reach"], 6)

    def test_reasons_are_listed(self):
        s = score_business(biz(years=None))
        self.assertIn("owner-run", " ".join(s["reasons"]["fit"]))


class ValidateTests(unittest.TestCase):
    def test_email_without_source_rejected(self):
        with self.assertRaises(BatchError) as cm:
            validate_business(biz(email="guess@acme.example", email_kind="person"))
        self.assertIn("Acme Heating", str(cm.exception))

    def test_email_kind_without_email_rejected(self):
        with self.assertRaises(BatchError):
            validate_business(biz(email_kind="person", email_source="x"))

    def test_bad_skip_rejected(self):
        with self.assertRaises(BatchError):
            validate_business(biz(skip="maybe"))

    def test_rating_out_of_range_rejected(self):
        with self.assertRaises(BatchError):
            validate_business(biz(rating=7))

    def test_tie_without_source_rejected(self):
        with self.assertRaises(BatchError):
            validate_business(biz(community_ties=[{"text": "church"}]))

    def test_missing_name_rejected(self):
        b = biz()
        del b["name"]
        with self.assertRaises(BatchError):
            validate_business(b)


class RankTests(unittest.TestCase):
    def batch(self, businesses, category="HVAC", conflicts=None):
        return {"category": category, "search": "hvac near Loma Linda, CA",
                "date": "2026-10-07", "conflict_deals": conflicts or [],
                "businesses": businesses}

    def test_skips_are_separated(self):
        r = rank(self.batch([biz(), biz(name="Chain Air", phone="2", skip="franchise")]))
        self.assertEqual([x["business"]["name"] for x in r["ranked"]], ["Acme Heating"])
        self.assertEqual(r["skipped"][0]["reason"], "franchise")

    def test_order_total_then_reviews_then_name(self):
        r = rank(self.batch([
            biz(name="B Co", reviews=120, phone="1"),
            biz(name="A Co", reviews=120, phone="2"),
            biz(name="C Co", reviews=500, phone="3"),
            biz(name="Low", owner_run=False, phone="4"),
        ]))
        self.assertEqual([x["business"]["name"] for x in r["ranked"]],
                         ["C Co", "A Co", "B Co", "Low"])

    def test_duplicate_phone_scored_once_with_warning(self):
        r = rank(self.batch([biz(), biz(name="Acme Heating & Air")]))
        self.assertEqual(len(r["ranked"]), 1)
        self.assertTrue(any("duplicate" in w.lower() for w in r["warnings"]))

    def test_exclusivity_flag_ignores_case_and_spaces(self):
        r = rank(self.batch([biz()], category="hvac ",
                            conflicts=[{"trade": "HVAC", "deal": "Daniels HVAC Territory"}]))
        self.assertIn("conflicts with Daniels HVAC Territory", r["ranked"][0]["flags"])

    def test_no_flag_for_other_trade(self):
        r = rank(self.batch([biz()], category="Roofing",
                            conflicts=[{"trade": "HVAC", "deal": "Daniels HVAC Territory"}]))
        self.assertEqual(r["ranked"][0]["flags"], [])

    def test_invalid_business_fails_whole_batch(self):
        with self.assertRaises(BatchError):
            rank(self.batch([biz(), biz(name="X", phone="1", email="a@b.c", email_kind="person")]))


if __name__ == "__main__":
    unittest.main()
```

- [ ] **Step 3: Run to verify it fails**

Run: `cd ~/Herd/newblood/prospects/inland-trades/tools && python3 -m unittest test_score -v`
Expected: FAIL / ERROR with `ModuleNotFoundError: No module named 'score'`.

- [ ] **Step 4: Implement `score.py` (core only; rendering comes in Task 2)**

`prospects/inland-trades/tools/score.py`:
```python
"""Inland trades prospect scorer. Spec: docs/superpowers/specs/2026-10-07-inland-trades-prospecting-design.md"""
import re

SKIPS = ("in_crm", "client", "franchise")
EMAIL_KINDS = ("person", "generic")


class BatchError(ValueError):
    pass


def _norm(s):
    return re.sub(r"\s+", " ", (s or "")).strip().lower()


def _digits(s):
    return re.sub(r"\D", "", s or "")


def validate_business(b):
    name = b.get("name")
    if not name:
        raise BatchError("a business is missing its name")
    if b.get("skip") not in (None,) + SKIPS:
        raise BatchError("%s: unknown skip %r (use one of %s)" % (name, b.get("skip"), ", ".join(SKIPS)))
    rating = b.get("rating")
    if rating is not None and not (0 <= rating <= 5):
        raise BatchError("%s: rating %r is not between 0 and 5" % (name, rating))
    reviews = b.get("reviews")
    if reviews is not None and reviews < 0:
        raise BatchError("%s: negative review count" % name)
    email, kind, source = b.get("email"), b.get("email_kind"), b.get("email_source")
    if email:
        if not source:
            raise BatchError("%s: email %s has no email_source (never guess an address)" % (name, email))
        if kind not in EMAIL_KINDS:
            raise BatchError("%s: email_kind must be 'person' or 'generic'" % name)
    elif kind or source:
        raise BatchError("%s: email_kind/email_source set without an email" % name)
    for field in ("community_ties", "brand_peers", "timely"):
        for item in b.get(field) or []:
            if not item.get("text") or not item.get("source"):
                raise BatchError("%s: every %s entry needs text and source" % (name, field))


def score_business(b):
    reasons = {"fit": [], "opportunity": [], "way_in": [], "reach": []}

    fit = 0
    if b.get("owner_run"):
        fit += 10
        reasons["fit"].append("owner-run, local +10")
    if (b.get("years") or 0) >= 10:
        fit += 10
        reasons["fit"].append("%s years +10" % b["years"])
    if b.get("high_job_value"):
        fit += 10
        reasons["fit"].append("high job value +10")
    if b.get("commercial"):
        fit += 5
        reasons["fit"].append("commercial work +5")

    opp = 0
    gaps = b.get("web_gaps") or []
    if not b.get("website"):
        opp = 5
        reasons["opportunity"].append("no website +5")
    else:
        rating, reviews = b.get("rating"), b.get("reviews")
        if gaps and rating is not None and reviews is not None and rating >= 4.7 and reviews >= 100:
            opp += 15
            reasons["opportunity"].append("%.1f stars / %d reviews on a weak site +15" % (rating, reviews))
        if gaps:
            opp += 10
            reasons["opportunity"].append("web gaps (%s) +10" % "; ".join(gaps))

    way = 0
    if b.get("community_ties"):
        way += 12
        reasons["way_in"].append("community tie +12")
    if b.get("brand_peers"):
        way += 8
        reasons["way_in"].append("brand/dealer peer +8")
    if b.get("timely"):
        way += 5
        reasons["way_in"].append("timely moment +5")

    kind = b.get("email_kind")
    reach = 10 if kind == "person" else 6 if kind == "generic" else 2
    reasons["reach"].append({10: "named person's email", 6: "generic email", 2: "form/phone only"}[reach]
                            + " +%d" % reach)

    return {"fit": fit, "opportunity": opp, "way_in": way, "reach": reach,
            "total": fit + opp + way + reach, "reasons": reasons}


def rank(batch):
    businesses = batch.get("businesses") or []
    for b in businesses:
        validate_business(b)

    category = _norm(batch.get("category"))
    conflicts = [c["deal"] for c in batch.get("conflict_deals") or [] if _norm(c.get("trade")) == category]

    ranked, skipped, warnings, seen = [], [], [], {}
    for b in businesses:
        if b.get("skip"):
            skipped.append({"business": b, "reason": b["skip"]})
            continue
        key = _digits(b.get("phone")) or _norm(b.get("name"))
        if key in seen:
            warnings.append("Duplicate listing: %s matches %s (same phone/name); scored once"
                            % (b["name"], seen[key]))
            continue
        seen[key] = b["name"]
        ranked.append({"business": b, "score": score_business(b),
                       "flags": ["conflicts with %s" % d for d in conflicts]})

    ranked.sort(key=lambda r: (-r["score"]["total"], -(r["business"].get("reviews") or 0),
                               _norm(r["business"]["name"])))
    return {"ranked": ranked, "skipped": skipped, "warnings": warnings}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `cd ~/Herd/newblood/prospects/inland-trades/tools && python3 -m unittest test_score -v`
Expected: all tests PASS (19 tests, OK).

- [ ] **Step 6: Commit**

```bash
cd ~/Herd/newblood
git add prospects/inland-trades/.gitignore prospects/inland-trades/tools/score.py prospects/inland-trades/tools/test_score.py
git commit -m "feat(prospecting): inland trades scorer core

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Markdown rendering + CLI

**Files:**
- Modify: `prospects/inland-trades/tools/score.py` (append)
- Modify: `prospects/inland-trades/tools/test_score.py` (append a test class)

**Interfaces:**
- Consumes: `rank(batch) -> dict` from Task 1.
- Produces:
  - `render(batch: dict, result: dict, top: int = 8) -> str` (Markdown: header with counts; a `### N. Name (City) — total/100` block per top business with one line per score part and any flags; a "Skipped" list; an "Everyone else" table; a "Warnings" list when non-empty).
  - CLI: `python3 tools/score.py batches/<file>.json [--top N]` prints `render(...)`; on `BatchError` prints `ERROR: <message>` to stderr and exits 2.

- [ ] **Step 1: Write the failing tests** (append to `test_score.py`, above the `if __name__` line)

```python
import json
import os
import subprocess
import sys
import tempfile

from score import render

HERE = os.path.dirname(os.path.abspath(__file__))


class RenderTests(unittest.TestCase):
    def sample(self):
        return {"category": "HVAC", "search": "hvac near Loma Linda, CA", "date": "2026-10-07",
                "conflict_deals": [{"trade": "HVAC", "deal": "Daniels HVAC Territory"}],
                "businesses": [biz(name="A Co", phone="1"), biz(name="B Co", phone="2", owner_run=False),
                               biz(name="C Co", phone="3", skip="in_crm")]}

    def test_render_counts_blocks_and_table(self):
        b = self.sample()
        md = render(b, rank(b), top=1)
        self.assertIn("seen 3 · skipped 1 · scored 2", md)
        self.assertIn("### 1. A Co (Redlands)", md)
        self.assertIn("conflicts with Daniels HVAC Territory", md)
        self.assertIn("| B Co |", md)          # second business lands in the table
        self.assertIn("C Co: in_crm", md)

    def test_fewer_than_top_is_fine(self):
        b = self.sample()
        md = render(b, rank(b), top=8)
        self.assertIn("### 2. B Co", md)
        self.assertIn("_none_", md)            # empty "everyone else"

    def test_cli_error_exit_code(self):
        bad = self.sample()
        bad["businesses"][0].update(email="x@y.z", email_kind="person")
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False) as f:
            json.dump(bad, f)
        p = subprocess.run([sys.executable, os.path.join(HERE, "score.py"), f.name],
                           capture_output=True, text=True)
        self.assertEqual(p.returncode, 2)
        self.assertIn("never guess", p.stderr)

    def test_cli_ok(self):
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False) as f:
            json.dump(self.sample(), f)
        p = subprocess.run([sys.executable, os.path.join(HERE, "score.py"), f.name, "--top", "1"],
                           capture_output=True, text=True)
        self.assertEqual(p.returncode, 0, p.stderr)
        self.assertIn("### 1. A Co", p.stdout)
```

- [ ] **Step 2: Run to verify it fails**

Run: `cd ~/Herd/newblood/prospects/inland-trades/tools && python3 -m unittest test_score -v`
Expected: ERROR `ImportError: cannot import name 'render'`.

- [ ] **Step 3: Implement** (append to `score.py`)

```python
def render(batch, result, top=8):
    ranked, skipped, warnings = result["ranked"], result["skipped"], result["warnings"]
    seen = len(batch.get("businesses") or [])
    out = ["# %s — batch %s" % (batch.get("category", "").strip(), batch.get("date", "")),
           "",
           "Search: `%s` · seen %d · skipped %d · scored %d"
           % (batch.get("search", ""), seen, len(skipped), len(ranked)),
           ""]
    for i, r in enumerate(ranked[:top], 1):
        b, s = r["business"], r["score"]
        out.append("### %d. %s (%s) — %d/100" % (i, b["name"], b.get("city") or "?", s["total"]))
        for part, label, cap in (("fit", "Fit", 35), ("opportunity", "Opportunity", 30),
                                 ("way_in", "Way in", 25), ("reach", "Reachability", 10)):
            out.append("- %s %d/%d: %s" % (label, s[part], cap, "; ".join(s["reasons"][part]) or "nothing found"))
        for f in r["flags"]:
            out.append("- ⚠️ %s" % f)
        out.append("")
    out += ["## Skipped", ""]
    out += ["- %s: %s" % (x["business"]["name"], x["reason"]) for x in skipped] or ["_none_"]
    out += ["", "## Everyone else", ""]
    rest = ranked[top:]
    if rest:
        out += ["| Business | City | Score | Why not higher |", "|---|---|---|---|"]
        for r in rest:
            s = r["score"]
            weakest = min(("fit", 35), ("opportunity", 30), ("way_in", 25), ("reach", 10),
                          key=lambda p: s[p[0]] / float(p[1]))[0]
            out.append("| %s | %s | %d | weakest: %s |" % (r["business"]["name"],
                                                         r["business"].get("city") or "?",
                                                         s["total"], weakest.replace("_", " ")))
    else:
        out.append("_none_")
    if warnings:
        out += ["", "## Warnings", ""] + ["- %s" % w for w in warnings]
    return "\n".join(out) + "\n"


def main(argv=None):
    import argparse
    import json
    import sys

    ap = argparse.ArgumentParser(description="Score an inland trades prospect batch")
    ap.add_argument("batch")
    ap.add_argument("--top", type=int, default=8)
    args = ap.parse_args(argv)
    with open(args.batch) as f:
        batch = json.load(f)
    try:
        result = rank(batch)
    except BatchError as e:
        sys.stderr.write("ERROR: %s\n" % e)
        return 2
    sys.stdout.write(render(batch, result, args.top))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd ~/Herd/newblood/prospects/inland-trades/tools && python3 -m unittest test_score -v`
Expected: all tests PASS (23 tests, OK).

- [ ] **Step 5: Commit**

```bash
cd ~/Herd/newblood
git add prospects/inland-trades/tools/score.py prospects/inland-trades/tools/test_score.py
git commit -m "feat(prospecting): batch markdown render + CLI

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Playbook, tracker, email rules, batch template

**Files:**
- Create: `prospects/inland-trades/README.md`
- Create: `prospects/inland-trades/tracker.md`
- Create: `prospects/inland-trades/email-rules.md`
- Create: `prospects/inland-trades/batch-template.md`
- Modify: `~/Herd/CLAUDE.md` (one rule line, outside this repo, not committed here)

**Interfaces:**
- Consumes: the CLI from Task 2 (`python3 tools/score.py batches/<file>.json --top 8`).
- Produces: the procedure Task 4 follows, and the signature block every draft uses.

- [ ] **Step 1: Get the signature address from Jeremy**

newblood.com shows no public mailing address (checked 10/7). Ask Jeremy: "What mailing address should go in the prospecting email signature (needed for CAN-SPAM)? A PO box is fine." Do not proceed to Step 3 without his answer.

- [ ] **Step 2: Write `README.md` (the playbook)**

```markdown
# Inland Trades Prospecting — session playbook

Spec: `docs/superpowers/specs/2026-10-07-inland-trades-prospecting-design.md`. One session = one category. Claude works as Jeremy's assistant; Jeremy sends every email.

## Before the sweep
1. Read `tracker.md`; take the next category in Jeremy's order unless he names one.
2. Pull the skip lists: `cd ~/Herd/customer-relationship-builder && npm run --silent crm -- find "<name>"` per candidate later; list live same-trade deals now with `crm -- pipeline` and `crm -- find <trade>` and write them into the batch JSON's `conflict_deals`.
3. Load Chrome tools in one ToolSearch call, call `tabs_context_mcp`, open a NEW tab.

## Sweep (max ~40 businesses)
4. Maps search: `<category> near Loma Linda, CA`. Scroll the list. Record name, city, rating, reviews, website, phone, Maps category. Keep ~15 miles (Redlands, San Bernardino, Colton, Highland, Grand Terrace, Yucaipa, Mentone, Bloomington, Rialto edge).
5. Mark skips before deeper work: in CRM (`crm -- find`), current/past client (CRM or `qbo -- customers <name>`), franchise/national chain.
6. For each remaining business, visit the site (~2-3 min): owner + founding year; contact options and any email; community signals (church, schools, youth sports, Chamber, faith language, "family-owned since"); brand/dealer affiliations; web read (dated/template, broken links, mobile, target cities). Record each tie with the URL it came from.
7. Email search order: site → contact page → Facebook About → BBB → Google Business Profile. Record `email_source`. None found → leave email null (form/phone only). NEVER guess an address.
8. Stop immediately on a captcha, "unusual traffic" page or block; tell Jeremy. No logins, no form submissions, no messages.

## Score and write up
9. Save observations to `batches/<YYYY-MM-DD>-<category-slug>.json` (field list in `tools/score.py` / plan Task 1).
10. Run `cd ~/Herd/newblood/prospects/inland-trades && python3 tools/score.py batches/<file>.json --top 8`. Fix any `ERROR:` it prints; never silence one by inventing data.
11. Build `<YYYY-MM-DD>-<category-slug>.md` from `batch-template.md`: paste the scorer output, then for each top card add Contact, Way in (one sentence + source link), Internal notes (web gaps, never in the email) and the draft email per `email-rules.md`.
12. Hand Jeremy the batch: top cards in chat, file path, and the question "Which of these should go into the CRM?"

## After Jeremy picks
13. Per yes: propose `crm -- company add` (if new) and `crm -- deal add "<Business> Territory" --company "<Business>" --stage LEAD --next <send date> --note "<score>/100 · way in: ... · contact: ..."`; run on his yes; report the output lines.
14. Update `tracker.md` (status, date, file, CRM count, re-sweep = date + 6 months), then the hub per `~/Herd/CLAUDE.md` (newblood `TASKS.md`, `_hub/PROJECTS.md`, `_hub/TODAY.md` if listed).
15. Follow-up rhythm: one nudge ~7 days after Jeremy confirms a send; still quiet → NURTURE with a low-key date.
```

- [ ] **Step 3: Write `email-rules.md`** (insert Jeremy's address from Step 1 where marked)

````markdown
# First-touch email rules (inland trades)

- Open on the familiar connection (community tie, brand peer, or timely moment), in one natural sentence.
- One short paragraph on who Jeremy is: New Blood, local, and a relevant peer only if it fits (e.g. built the Overhead Door Baltimore site for an OHD dealer).
- One soft yes/no ask: would a free look at how customers find them be useful? Do not name the "Four Gaps Audit" in the first touch.
- Never: em dashes, pricing, anything negative about their website, review counts or rankings not verified on their own listing.
- Voice: plain, direct, warm; check against `docs/voice-samples/`. If the contact exists in the CRM, run `crm -- contact show <name>` and match its tone cue.
- Print the draft (subject + body) in one plain fenced code block, never a blockquote.

Signature (every draft):
```
Jeremy Oms
New Blood
<mailing address from Jeremy, Task 3 Step 1>
newblood.com

If this isn't useful, just say so and I won't follow up.
```
````

- [ ] **Step 4: Write `batch-template.md`**

````markdown
# <Category> — batch <YYYY-MM-DD>

<paste scorer header line>

## Top prospects

### 1. <Business> (<City>) — <total>/100
- <score lines from the scorer, including any ⚠️ conflict flag>
- **Contact:** <owner if public> · <email> (source: <where>) · <phone> · <website>
- **Rating:** <stars> from <reviews> on Google (as listed <date>) · in business since <year> (source)
- **Way in:** <one sentence> (<source link>)
- **Internal notes (never in the email):** <web gaps for a future Four Gaps Audit>

```
Subject: <subject>

<body>

<signature from email-rules.md>
```

<repeat per top prospect>

## Skipped
<from the scorer>

## Everyone else
<from the scorer>
````

- [ ] **Step 5: Write `tracker.md`**

```markdown
# Inland trades — category tracker

Order is Jeremy's to change. Re-sweep 6 months after the last sweep.

| # | Category | Status | Last swept | Batch file | Added to CRM | Re-sweep due |
|---|---|---|---|---|---|---|
| 1 | HVAC | next | | | | |
| 2 | Garage doors | queued | | | | |
| 3 | Roofing | queued | | | | |
| 4 | Plumbing | queued | | | | |
| 5 | Electrical | queued | | | | |
| 6 | Solar | queued | | | | |
| 7 | Pest control | queued | | | | |
| 8 | Pool / landscape | queued | | | | |
```

- [ ] **Step 6: Add the hub pointer** in `~/Herd/CLAUDE.md` under `## Rules`, after the topic-clusters rule:

```markdown
- **Prospecting runs from the inland-trades playbook.** When Jeremy asks to prospect, find leads, or run the next category, follow `~/Herd/newblood/prospects/inland-trades/README.md` (one category per session, Maps in a new Chrome tab, ~40 max, never guess an email, he sends every email). (Added 10/7/2026.)
```

- [ ] **Step 7: Verify the playbook commands resolve**

Run: `cd ~/Herd/newblood/prospects/inland-trades && python3 tools/score.py --help && cd ~/Herd/customer-relationship-builder && npm run --silent crm -- find "Daniels"`
Expected: argparse usage text; then the Daniels deal/company/contact lines.

- [ ] **Step 8: Commit** (repo files only; `~/Herd/CLAUDE.md` is outside this repo)

```bash
cd ~/Herd/newblood
git add prospects/inland-trades/README.md prospects/inland-trades/tracker.md prospects/inland-trades/email-rules.md prospects/inland-trades/batch-template.md
git commit -m "docs(prospecting): inland trades playbook, tracker, email rules, template

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: HVAC batch 1 (the first real run)

**Files:**
- Create: `prospects/inland-trades/batches/2026-10-07-hvac.json` (git-ignored)
- Create: `prospects/inland-trades/2026-10-07-hvac.md` (git-ignored)
- Modify: `prospects/inland-trades/tracker.md`, `newblood/TASKS.md`, `_hub/PROJECTS.md`, `_hub/TODAY.md`

**Interfaces:**
- Consumes: the playbook (Task 3) and CLI (Task 2).
- Produces: the first batch handed to Jeremy; CRM writes only on his yes.

- [ ] **Step 1: Pre-sweep.** Run `crm -- find HVAC` and `crm -- find "Heating"`; confirm Daniels HVAC Territory (NURTURE) and record it in `conflict_deals` as `{"trade": "HVAC", "deal": "Daniels HVAC Territory"}`.

- [ ] **Step 2: Sweep** per README steps 3-8 in a new Chrome tab: `hvac near Loma Linda, CA`, ≤40 businesses. Daniels Heating & Air Conditioning is a `skip: "in_crm"` entry. Stop and report on any captcha.

- [ ] **Step 3: Score.** Run `python3 tools/score.py batches/2026-10-07-hvac.json --top 8`.
Expected: exit 0; header `seen N · skipped ≥1 · scored M`; every top block shows `⚠️ conflicts with Daniels HVAC Territory`.

- [ ] **Step 4: Write the batch file** from `batch-template.md`. Check every draft: no `—` characters (`grep -c '—' 2026-10-07-hvac.md` counts only header/score lines, none inside code blocks), no prices, no website criticism, signature present.

- [ ] **Step 5: Hand off to Jeremy.** Show the top cards and drafts in chat, give the file path, note the Daniels conflict flag on every card, ask which go into the CRM. Do not write to the CRM without his yes.

- [ ] **Step 6: CRM + tracker + hub.** For each yes, propose/confirm/run per README step 13 and report the output lines. Update `tracker.md` row 1 (done, date, file, count, re-sweep 2027-04-07), newblood `TASKS.md`, `_hub/PROJECTS.md` (⟳ date + one line), `_hub/TODAY.md` if prospecting is listed.

- [ ] **Step 7: Commit tracker only**

```bash
cd ~/Herd/newblood
git add prospects/inland-trades/tracker.md
git commit -m "chore(prospecting): HVAC batch 1 swept

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```
