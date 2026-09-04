---
name: fairmd-lipids
description: Query and analyze the FAIRMD Lipids Databank (lipids.fairmd.org) — atomistic MD simulations of lipid bilayers scored against NMR order parameters and X-ray form factors. Use this whenever someone asks which force field is best for a lipid (POPC, POPE, POPS, DPPC, cholesterol mixtures…), asks about simulation quality, area per lipid, bilayer thickness, order parameters, form factors, NMRlipids, or wants counts/coverage/statistics across the databank. Also use it whenever the mcp__FAIRMD_lipids_databank__* tools are available and the question touches lipid membranes or force fields, even if the databank is never named — the scores have interpretation pitfalls (regions never measured reported as 0, overlapping scored subsets, ranking only the scored minority) that produce confidently wrong claims without it.
---

# FAIRMD Lipids Databank

The databank holds MD trajectories of lipid bilayers, each optionally scored against
experiment on two independent axes: **OP quality** (agreement with NMR order
parameters) and **FF quality** (agreement with X-ray form factors). Higher is better
on both.

The tools are read-only and rate-limited. The queries are straightforward; the
*interpretation* is where plausible, wrong answers come from. Read "Traps" before
running anything you intend to report.

## Filter mechanics

Range filters (`quality_total_min`/`_max`, `ff_quality_*`, `quality_hg_*`,
`quality_tails_*`, `area_per_lipid_*`, `bilayer_thickness_*`, `temperature_*`) may be
used one-sided, `0` is a valid bound, and rows with no value for the metric never
pass — so `quality_total_min: 0` selects exactly the OP-scored set. Multiple
`force_fields` patterns OR together and combine correctly with any other filter in
the same call. `contains` is case-insensitive, so `"charmm"` matches `CHARMM36`,
`Charmm36` and `Charmm-Drude polarizable2023` alike.

(Server versions before September 2026 silently ignored a lone `_min`/`_max` bound
and dropped other filters whenever several force-field patterns were batched. The
tell: a lone bound such as `quality_total_min: 0` returning the unfiltered set
total. On such a server, supply both bounds — e.g. `-1` and `2` for a [0, 1] score —
and use exactly one force-field pattern per quality-filtered call.)

## Traps

These are not hypothetical. Each one was found producing a wrong answer.

**`best-simulations` only ranks the scored subset.** Its `ranked_count` field (named
`total` on servers before September 2026) counts ranked simulations only: for POPE it
reports 25 while the databank actually holds 69 POPE trajectories. That is correct
behaviour for a ranking tool but disastrous if you read it as a count. Use
`advanced-trajectory-search` for anything involving "how many".

**A `0` in a component score can mean "never measured", not "measured and bad".**
No DPPC trajectory in the databank has a nonzero `op_quality_tails` — every scored
one reports exactly `0` — while 72 POPC trajectories have real tail scores. There is
simply no acyl-chain NMR reference for DPPC here. Read those zeros as "scored badly"
and you will report a fictional catastrophe. The tell is `op_quality_total` being
*exactly* equal to `op_quality_headgroups`: where both regions are genuinely scored,
the total is a blend and sits between them. Before comparing a region across force
fields, confirm that region is scored at all for that lipid — one range query on
`quality_tails_min`/`max` over the lipid answers it. Say "not measured" when that is
what the data means.

Because of these, **every aggregate you report should reconcile against an independent
whole-set query.** Get the set total once with a single unfiltered query, tally your
per-family numbers, and check they sum. If they don't, you have hit a bad query or a
missed overlap. This check has caught real errors every time it was run; treat a
mismatch as a bug in your query, not a rounding issue.

## Two ways to count — pick by set size

**Small set (roughly ≤200 trajectories — a single lipid usually qualifies): list it
and tally the rows yourself.** `advanced-trajectory-search` with `lipids: ["DPPC"]`
and `per_page: 100` returns every row with its force field and both quality fields.
Tallying from rows gives you the exact force-field strings and lets you spot oddities
the counts would hide. It costs one or two calls. This is the better default whenever
the set fits.

**Large set (a whole-databank sweep, or POPC at 525): count with filters.**
`per_page: 1` still returns the true `total`, so each count is a few hundred tokens
instead of paging through hundreds of rows. One call per (family × subset × metric)
cell — a family's patterns can share one call. Reconcile at the end.

Either way, get the set total once from an unfiltered query and check your parts sum
to it.

## Force-field families

The databank has ~80 distinct force-field strings, most of them one-off variants
("CHARMM36 with NBFIX for calcium ions"). Charting 80 bars is useless; group them.
`scripts/families.py` holds the canonical patterns, the overlap resolution, and a
reconciliation check — run it rather than rederiving the taxonomy.

Two things the grouping has to get right:

*Drude is not CHARMM36.* The pattern `charmm` catches the polarizable Drude variants
too. Subtract the `drude` count to get classical CHARMM36, and report Drude
separately — they behave very differently (see below).

*A shared name is not a shared force field.* The free academic **OPLS-AA** and
Schrödinger's commercial **OPLS3e/OPLS4** differ in functional form and
parameterisation — they are not comparable, and a single "OPLS" row averaging them
asserts something false. Keep them as separate families. In this data they never even
meet: OPLS-AA has exactly one trajectory (POPE), OPLS3e/OPLS4 have none, so an "OPLS"
row would look strong for POPC and terrible for POPE while describing two disjoint
force fields. Groupings that *are* one lineage and merge safely: Lipid14/17/21,
Parsley/Sage, OPLS3e→OPLS4. Before folding a new name into a family, ask whether it
shares developers, functional form and parameterisation lineage — string similarity is
not the test. The domain expert reading your table will notice if it isn't.

*Hybrids belong to neither parent.* A force field that splices two lineages is not a
variant of either, and folding it into one corrupts both rows — the parent gains
trajectories it did not produce, and the hybrid's own performance disappears. Give
them their own family. The test is whether parameters for a **single molecule** come
from two lineages:

- *Hybrid:* `C36_Slipids_Hybrid` (a refit of two lipid models);
  `GROMOS-CKP, Berger/Chiu NH3 charges and PME` (Berger/Chiu headgroup charges on a
  GROMOS-CKP lipid); `OPLSAA-compatible Berger-DPPC-06` (Berger DPPC ported to
  OPLS-AA conventions). Thirteen trajectories in total.
- *Not hybrid:* combinations of whole **molecules**, which is ordinary practice rather
  than a new lipid model. `Berger lipids + Gromos 53A6` (Berger for the lipids, GROMOS
  for everything else) stays in Berger, as does `Berger and Modified Höltje model for
  cholesterol`; every "X for lipids and Y for ions" entry stays with X.

Judge a new name by that test, not by whether it contains a `+` or the word "hybrid" —
a graft and a co-solvent choice look alike in a name string and mean different things.
Read the deposition when it is genuinely ambiguous.

*Names claimed by more than one pattern* are credited once and subtracted from every
other family that matched; the two hybrids above match their parents' patterns as well
as the Hybrid family's, so both parents give them up. `Berger lipids + Gromos 53A6` is
the one remaining plain overlap (→ Berger, subtracted from GROMOS). With these rules
the families sum exactly to the set total — `scripts/families.py --check` enforces it.

## Reading the quality scores honestly

The scores are seductive and easy to over-read. What follows is where the analysis
usually goes wrong.

**The headgroup/tails split is diagnostic, not modular.** `op_quality_headgroups` and
`op_quality_tails` describe one simulation's behaviour in two regions; they are not
independent components you can mix and match. Headgroup order parameters depend
strongly on area per lipid, which is set mostly by tail packing. So "force field A has
the best headgroups, B the best tails, therefore graft them" does not follow — and
when someone asks about hybrids, say so. (Hybrids do exist and can work: the
`C36_Slipids_Hybrid` entries are a real, refitted example that beat both parents on
order parameters while *losing* form-factor agreement. Look them up rather than
speaking generally.)

**OP and FF disagree, and the disagreement is the finding.** A rank-product ranking
can put a simulation first on the strength of one axis while it scores near zero on
the other — for POPE, the top-ranked Slipids run scores 1.00 on form factor and 0.006
on headgroups. Always show the component scores, not just the composite. When someone
asks "which is best", the honest answer usually names the observable first.

**FF quality is heavily confounded with which experiment a trajectory was matched
to.** CHARMM36 POPC looks catastrophic (~0.04 median) in the 298–300 K subset and fine
(0.70–0.74) at 303 K, because those are different reference datasets. Before declaring
a force field bad on form factors, check whether the low scorers cluster at one
temperature.

**Scores may be normalized within the set.** Values land on exactly 1.000 and exactly
0.000 at the extremes, which suggests min–max normalization rather than an absolute
goodness-of-fit. Treat them as an ordering, and say so rather than quoting them as
absolute agreement.

**Sanity-check the geometry.** A high OP score with an area per lipid far from the
experimental value (POPC is ~64–65 Å² near 300 K) is a red flag, not a triumph. Flag
those runs instead of citing them.

**Within-force-field spread is often larger than between-force-field differences.**
Slipids POPE gives 59.0 Å² in one trajectory and 67.2 Å² in another; CHARMM36 POPC
order parameters range 0.37–0.67 under one name. Report the spread, and prefer medians
over a single best entry. A "best force field" claim that rests on one trajectory is
not a result.

**OP-scored and FF-scored sets overlap — never add them.** Databank-wide (Aug 2026):
151 have OP, 209 have FF, 116 have both, so 244 of 893 have *any* score. Roughly
three-quarters of the databank has never been compared to experiment at all, and
coverage is uneven in a way that does not track popularity: CHARMM36 has 387
trajectories and 47 OP scores; OpenFF/Sage/Parsley has 8 trajectories and 7. Mention
the evidence base when reporting a comparison — a ranking over 14 scored trajectories
deserves that caveat.

## Workflow

1. **Scope the question.** "Which force field for X" is a quality question — go to
   step 3. "How much / how many" is a coverage question — go to step 2. Many good
   answers need both, because a ranking means little without knowing how thin it is.

2. **Coverage.** Get the set total (`advanced-trajectory-search`, `per_page: 1`, no
   filters or just the lipid filter). If it is small, list the rows and tally. If it
   is large, go per family — total, OP-scored, FF-scored. Either way reconcile;
   `scripts/families.py --check` does the arithmetic.

3. **Quality.** `best-simulations` for the ranked shortlist, then
   `advanced-trajectory-search` with `lipids` to catch the unscored remainder so you
   know the denominator. Pull component scores and area per lipid for every entry you
   plan to cite; `get-trajectory` for full detail on the few you highlight.

4. **Report.** Lead with the trade-off rather than a winner, show component scores,
   name the denominator, and flag the geometry outliers. Link cited trajectories as
   `https://lipids.fairmd.org/trajectories/<id>`.

Note that `lipids: ["POPC"]` matches every trajectory *containing* POPC, mixtures
included. Say which you mean — single-component and mixture populations behave very
differently, and mixtures are far less likely to be scored.

## Deliverables

For a comparison, a table in the reply usually suffices: one row per trajectory or
family, columns for OP total / headgroup / tails / FF quality / area per lipid.

For a coverage or survey question, or anything the user wants to share, build an HTML
page and publish it as an Artifact. `references/report-pattern.md` describes the
layout that worked — headline stats, per-subset count panels scaled independently, and
a shaded matrix for evaluated coverage — along with the reasoning behind those choices.
Load the `dataviz` skill before writing chart code and `artifact-design` before writing
the page.

Embed the reconciliation totals in the page as a console assertion. It costs three
lines and it means a future edit that breaks a number fails loudly instead of shipping
a wrong chart.

## Connecting

If the `mcp__FAIRMD_lipids_databank__*` tools are absent, the server is a public,
unauthenticated, read-only streamable-HTTP MCP endpoint at
`https://lipids.fairmd.org/mcp/fairmd-lipids` (discoverable via the site's
`llms.txt`). In Claude Desktop it goes in Settings → Connectors → Add custom connector.
