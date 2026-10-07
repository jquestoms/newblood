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
