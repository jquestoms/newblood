# Cohort Selects Gallery — Design Spec

**Date:** 2026-06-23
**Status:** Approved for planning
**Owner:** Jeremy (New Blood, Inc.)
**First use:** Loma Linda University — Physician Assistant Sciences Masters graduation cohort (~37 students)

---

## 1. Problem & goal

Jeremy shot graduation portraits for a Masters cohort. Each student needs to choose
their own preferred frame from a small curated set, with **minimal admin overhead on
the school's side**. Today the bottleneck is one person internally chasing ~37 people
for their preferences.

The system must:

- Let each student see **only their own** curated frames (their best 2–4), not the full take.
- Let them pick **one favorite**, with an optional crop/comment note.
- **Track and report selections automatically** so no one has to chase responders.
- Feed each chosen frame into Jeremy's **retouching queue** (the gallery is for *picking*,
  not delivering final files).
- Be **reusable** for future cohorts with new data, not new code.

A student's selection is **not** the final deliverable. It is a signal that moves the chosen
frame into retouching. Finals are delivered later, out of band (see Out of Scope).

## 2. Build vs. buy decision

**Decision: BUILD**, as an owned, reusable asset.

Off-the-shelf proofing platforms (Pixieset, ShootProof, CloudSpot) cover ~90% of a one-off
job cheaply, but stumble on this project's specifics: true per-student isolation at scale
(37 isolated galleries), reminders targeted only at *non-responders*, and a clean export
keyed to a retouch queue. More decisively, this is a strategic asset for New Blood — a
reusable "client-selects" system that powers any cohort/team/headshot selection job and is
a potential productized service line. Owning the schema makes isolation, non-responder
reminders, and queue export trivial.

## 3. Key constraints that shaped the design

- **No student emails exist.** Jeremy has only a **list of full names**. This removes
  per-student invite/reminder emails entirely and drives the name-based access model.
- **The school curates first.** Valerie's team signs off on the curated set internally
  before any student is involved. Jeremy operates as the school's photography vendor.
- **Images are not critical.** Proof leakage is low-stakes; no watermarking or download
  blocking is required.
- **Single admin.** Jeremy is the only operator. Valerie receives email digests, not a login.

## 4. Architecture & stack

- **Next.js (App Router) on Vercel**, on its own subdomain. Proposed: `selects.newblood.com`.
- **Postgres + Prisma** for relational data.
- **Vercel Blob** for image storage (uploaded via presigned/client-upload, not streamed
  through serverless functions).
- **Amazon SES** for the single outbound email type: the non-responder digest to Jeremy + Valerie.
- **Admin auth:** single admin (Jeremy). Lightweight — magic-link to Jeremy's email or an
  env-configured password. No school-facing auth in MVP.
- **Image serving:** plain — full-resolution, downloadable, **no watermark, no download
  blocking** (explicit choice; "not critical images").

## 5. Data model (cohort-keyed core)

Everything keys off `Cohort` so the next cohort is new data, and teardown/re-spin is trivial.

- **Cohort** — `name`, `school`/`program`, `brandingConfig`, `deadline`, `retentionDate`,
  `status` (draft / live / locked / archived), public link token.
- **Student** — belongs to a Cohort. `fullName`, optional `disambiguationTag` (for duplicate
  names), `normalizedNameKey` (powers typeahead + folder match).
- **Frame** — belongs to a Student. `blobKey`, `originalFilename`, `sortOrder`.
- **Selection** — belongs to a Student. `frameId`, optional `note`, `source`
  (`student` | `studio`), `createdAt`/`updatedAt`, `locked`.

Cardinality: one Cohort → ~37 Students → each 2–4 Frames → 0 or 1 Selection.

## 6. Curation / ingest flow (admin side) — with mismatch guard

1. **Create Cohort**; import the **roster** (paste or CSV of the ~37 full names). The roster
   is the single source of truth — it powers both the student typeahead and the folder match.
2. **Export from Lightroom** into **one folder per student**, each folder named from the roster.
3. In the admin, **select the parent folder** (browser folder-picker). Files upload **directly
   to Vercel Blob via client upload** (not through the server). Each top-level folder name maps
   to a Student via the normalized name key.
4. **Reconciliation preview before publish** — the safety net. Shows e.g.
   *"37 students · 35 matched · 2 folders unmatched · 1 student has 0 frames."* Jeremy resolves
   mismatches here. **Nothing goes live until Jeremy approves this screen.** This is the single
   most important anti-mismatch mechanism in the system.

## 7. Student flow (public side)

Shared link → co-branded landing (Loma Linda / program prominent + "photography by New Blood")
→ **roster typeahead**: student types and selects their own name from the known list → sees
their **2–4 frames** → chooses **one** → **optional note** field (crop/comment requests) →
**explicit confirmation** step → done, with a "you can change this until [deadline]" message.

- Re-entry by name lets a student **change their pick until lock**.
- **Single pick** only (not ranked — only one frame is retouched).
- Mobile-first; full-resolution images.

## 8. Admin dashboard & notifications

- **Live status table:** per student — picked / not picked, chosen-frame thumbnail, note,
  source (`student` vs `studio`), timestamp. Header counts ("24 / 37 selected").
- **Auto digest (SES):** scheduled cadence near the deadline; emails Jeremy + Valerie a list
  of **only the non-responders**, so Valerie nudges via the school's own channel (LMS/class email).
- **Deadline + lock:** at the deadline, picks freeze. Remaining non-responders get the
  **studio-pick fallback** — Jeremy selects a best frame on their behalf, flagged `studio` in
  the data — so every student still gets a retouched portrait.
- **Export to retouch queue:** a **CSV** (student · chosen filename · note · source · timestamp)
  plus a **manifest of the chosen frames**, so the editing pipeline knows exactly what to pull.

## 9. Branding

**Co-branded.** School/program identity prominent for student trust and an "official" feel,
with a tasteful "photography by New Blood" credit. Stored per-cohort in `brandingConfig` so
each cohort can differ.

## 10. Privacy / FERPA posture

- **Vendor-of-the-school model.** The school authorizes the curated set before students are
  involved; graduation portraits are generally not FERPA "education records."
- **Name-typeahead trade-off (accepted):** the roster of names is discoverable to anyone with
  the link. Jeremy chose this over a shared access code; images are non-critical and each name
  exposes only that student's own 2–4 frames.
- **Mitigations baked in:** unguessable cohort link token; **archive + take the gallery offline
  after delivery** (controlled by `retentionDate`); each pick exposes only that student's frames.
- **Action item (non-code):** get Valerie's written OK to host the cohort's names + photos on
  the New Blood domain / Vercel Blob.

## 11. Scope & phasing

### Phase 1 — MVP (this cohort)
Cohort + roster import + folder ingest + reconciliation preview · student name-pick flow ·
admin dashboard · SES non-responder digest · deadline lock + studio-pick fallback ·
CSV + chosen-frames manifest export · archive/teardown. Cohort-keyed throughout, so the next
cohort is configuration, not a rewrite.

### Explicitly OUT of MVP
- Finals delivery back to students
- Watermarking / download blocking
- Ranked or backup picks
- Valerie login (digest emails only)
- Multi-cohort management UI

### Natural Phase 2 (later)
- Finals delivery back to students in the same system
- Cohort management / clone UI
- Optional Valerie read-only login

## 12. Defaults chosen

- **Subdomain:** `selects.newblood.com`
- **Export format:** CSV + chosen-frames manifest
- **Storage:** Vercel Blob

## 13. Open items for implementation planning

- Exact admin auth mechanism (magic-link vs env password) — decide in plan.
- Digest cadence specifics (e.g. start N days before deadline, every M days).
- Duplicate-name handling UX in the typeahead (disambiguation tag display).
- Reconciliation matching tolerance (exact vs normalized/fuzzy folder→name match).
