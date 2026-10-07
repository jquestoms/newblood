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


if __name__ == "__main__":
    unittest.main()
