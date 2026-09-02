#!/usr/bin/env python3
"""Force-field family taxonomy for the FAIRMD Lipids Databank.

The databank stores ~80 free-text force-field strings, most of them one-off
variants. This module holds the grouping used for reporting, the resolution for
names that span two families, and a reconciliation check.

Why a script rather than prose: the overlap arithmetic is fiddly and easy to get
subtly wrong by hand, and a silent double-count looks exactly like a real result.

Usage
-----
  python families.py --patterns
      Print the query plan: one line per (family, pattern, operator). Run one
      advanced-trajectory-search call per family with per_page=1 -- its patterns
      OR together and combine correctly with quality filters -- and record the
      returned `total` in counts.json under the FAMILY LABEL. On servers older
      than September 2026, batching patterns silently dropped any quality filter
      in the same call (see SKILL.md "Filter mechanics"); there, query one
      pattern at a time and record each total under the PATTERN string instead.

  python families.py --check counts.json
      Apply the overlap rules and verify the families sum to the set total.
      Exits non-zero on a mismatch, because a mismatch means a bad query.

counts.json shape -- raw totals, before overlap correction. Keys of "patterns"
are family labels holding the union total of one OR query (preferred: the union
cannot double-count a name matching two of the family's patterns):

  {
    "label": "All trajectories",
    "total": 893,
    "patterns": {"CHARMM36": 409, "CHARMM-Drude": 22, "Slipids": 135, ...},
    "overlaps": {"OPLSAA-compatible Berger-DPPC-06": 5,
                 "Berger lipids + Gromos 53A6": 1,
                 "GROMOS-CKP, Berger/Chiu NH3 charges and PME": 6}
  }

Per-pattern keys ("Lipid14": 1, "Lipid17": 13, ...) are the fallback for the
one-pattern-per-call plan; they are summed, so this only partitions correctly
when no name matches two patterns of the same family. A family label present in
"patterns" takes precedence over that family's per-pattern keys.

Omitted families are treated as zero, so the same file shape works for a lipid
subset where several families are absent.
"""

import argparse
import json
import sys

# (family label, search pattern). `contains` is case-insensitive server-side, so
# "charmm" catches CHARMM36 / Charmm36 / Charmm-Drude alike.
#
# AMBER, the OpenFF lineage and "Other" need several patterns because their names
# share no common substring. The patterns of one family can go in a single call;
# keep separate families in separate calls so each `total` maps to one family.
PATTERNS = [
    ("CHARMM36",            ["charmm"]),      # includes Drude; subtracted below
    ("CHARMM-Drude",        ["drude"]),
    ("Slipids",             ["slipids"]),
    ("AMBER Lipid14/17/21", ["Lipid14", "Lipid17", "Lipid21"]),
    ("Berger",              ["berger"]),
    ("ECC-lipids",          ["ECC-lipids"]),
    ("MacRog",              ["macrog"]),
    ("GROMOS",              ["gromos"]),
    # OPLS-AA and OPLS3e/OPLS4 share a name and nothing else -- see the note below.
    ("OPLS3e/OPLS4 (Schrödinger)", ["OPLS3e", "OPLS4"]),
    ("OPLS-AA (free)",      ["OPLS-aa"]),
    ("OpenFF/Sage/Parsley", ["OpenFF", "Sage", "Parsley"]),
    ("AMOEBA",              ["amoeba"]),
    ("GAFFlipid",           ["gaff"]),
    ("Other",               ["Orange", "Ulmschneider", "FF-development", "PROSECCO"]),
    # Hybrids belong to neither parent -- see HYBRIDS below.
    ("Hybrid (cross-lineage)", ["C36_Slipids_Hybrid",
                                "OPLSAA-compatible Berger-DPPC-06",
                                "GROMOS-CKP, Berger/Chiu NH3 charges and PME"]),
]

# A hybrid is not a variant of either parent, and averaging it into one of them
# corrupts both rows. The test is whether parameters for a SINGLE MOLECULE are
# drawn from two lineages:
#
#   hybrid      C36_Slipids_Hybrid -- a refit of two lipid models
#               GROMOS-CKP, Berger/Chiu NH3 charges -- Berger/Chiu headgroup
#                 charges grafted onto a GROMOS-CKP lipid
#               OPLSAA-compatible Berger-DPPC-06 -- Berger DPPC ported to
#                 OPLS-AA conventions
#
#   not hybrid  Combinations of whole MOLECULES, which is routine practice and
#               not a new lipid model: "Berger lipids + Gromos 53A6" (Berger for
#               lipids, GROMOS for the rest) stays in Berger, as does "Berger and
#               Modified Hoeltje model for cholesterol"; every "X for lipids and
#               Y for ions" entry stays with X.
#
# Judge a new name by that test, not by whether it contains a "+" or the word
# "hybrid". If in doubt, read the deposition -- a graft and a co-solvent choice
# look alike in a name string and mean different things.
HYBRIDS = [label for label, _ in PATTERNS if label.startswith("Hybrid")]

# A shared name is not a shared force field.
#
# The free academic OPLS-AA and Schrodinger's commercial OPLS3e/OPLS4 differ in
# functional form and parameterisation; averaging them into one "OPLS" row states
# something false. They are kept apart above, and the pattern "OPLS-aa" does not
# match "OPLSAA-compatible Berger-DPPC-06", so no subtraction is needed between
# them. Note also that a broad "opls" pattern would sweep up all three at once --
# do not reintroduce one.
#
# Groupings that ARE a single lineage, and are fine to merge: Lipid14/17/21
# (successive AMBER Lipid releases), Parsley/Sage (successive OpenFF releases),
# OPLS3e -> OPLS4 (successive Schrodinger releases).
#
# Apply the same test to any new name before folding it into a family: same
# developers, same functional form, same parameterisation lineage?

# Names claimed by more than one family pattern. Each is credited to exactly one
# family and subtracted from every other that matched, so the families partition
# the set. The two hybrids here match their parents' patterns as well as the
# Hybrid family's, so both parents give them up.
#   key -> (family that keeps it, [families that must give it up])
OVERLAPS = {
    "Berger lipids + Gromos 53A6":
        ("Berger", ["GROMOS"]),
    "GROMOS-CKP, Berger/Chiu NH3 charges and PME":
        ("Hybrid (cross-lineage)", ["Berger", "GROMOS"]),
    "OPLSAA-compatible Berger-DPPC-06":
        ("Hybrid (cross-lineage)", ["Berger"]),
    "C36_Slipids_Hybrid":
        ("Hybrid (cross-lineage)", ["Slipids"]),
}

FAMILIES = [label for label, _ in PATTERNS]


def resolve(patterns, overlaps):
    """Raw totals (family-label unions, or per-pattern) -> disjoint family counts."""
    fam = {}
    for label, pats in PATTERNS:
        if label in patterns:
            # Union total of one OR query over the family's patterns.
            fam[label] = patterns[label]
        else:
            # Per-pattern fallback; correct only if no name matches two patterns.
            fam[label] = sum(patterns.get(p, 0) for p in pats)

    # Drude matches "charmm" too; report classical CHARMM36 net of it.
    fam["CHARMM36"] -= fam["CHARMM-Drude"]

    for name, (keeper, losers) in OVERLAPS.items():
        n = overlaps.get(name, 0)
        if n:
            for loser in losers:
                fam[loser] -= n
    return fam


def main():
    ap = argparse.ArgumentParser(description=__doc__,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--patterns", action="store_true",
                    help="print the query plan and exit")
    ap.add_argument("--check", metavar="COUNTS_JSON",
                    help="resolve overlaps and reconcile against the set total")
    args = ap.parse_args()

    if args.patterns:
        print(f"{'family':22} {'pattern':46} operator")
        for label, pats in PATTERNS:
            for p in pats:
                print(f"{label:22} {p:46} contains")
        for name in OVERLAPS:
            print(f"{'(overlap)':22} {name:46} equals")
        print("\nRun one advanced-trajectory-search call per family (its patterns "
              "OR together), per_page=1, and record the returned `total` under "
              "the family label in counts.json.")
        return 0

    if not args.check:
        ap.print_help()
        return 0

    with open(args.check) as fh:
        data = json.load(fh)

    fam = resolve(data.get("patterns", {}), data.get("overlaps", {}))
    total = data["total"]
    got = sum(fam.values())
    label = data.get("label", "set")

    width = max(len(f) for f in FAMILIES)
    print(f"{label} — reconciliation\n")
    for f in FAMILIES:
        n = fam[f]
        pct = f"{n / total * 100:5.1f}%" if total else "    –"
        flag = "  <-- NEGATIVE" if n < 0 else ""
        print(f"  {f:<{width}}  {n:>5}  {pct}{flag}")
    print(f"  {'':<{width}}  {'-' * 5}")
    print(f"  {'sum':<{width}}  {got:>5}")
    print(f"  {'expected':<{width}}  {total:>5}")

    if got != total or any(v < 0 for v in fam.values()):
        print(f"\nMISMATCH: families sum to {got}, set total is {total} "
              f"(off by {got - total}).")
        print("A mismatch is a bad query, not a rounding issue. Check that every "
              "pattern was counted exactly once, that no pattern sweeps up another "
              "family's names, and that every overlap name appears in the "
              "overlaps map.")
        return 1

    print("\nOK — families partition the set exactly.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
