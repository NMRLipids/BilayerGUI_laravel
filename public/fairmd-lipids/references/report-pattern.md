# Report pattern

Read this when the deliverable is a page rather than a table in the reply — a
coverage survey, a force-field comparison someone wants to circulate, anything
that will outlive the conversation. For a quick answer, a markdown table in the
reply is the right size and this file is overkill.

Load `dataviz` before writing chart code and `artifact-design` before writing the
page. What follows is the layout that survived contact with this data, and why —
so you can depart from it when the question is shaped differently.

## Shape

1. **Masthead** — the question as a title, a one-paragraph lede naming the finding.
   Include the databank's `last_update` and the query date: these numbers move.
2. **Headline stats** — one tile per subset (All / lipid A / lipid B), each showing
   n plus the OP-scored and FF-scored counts with percentages. This front-loads the
   evidence base, which is the thing readers most often fail to notice.
3. **Count panels** — one panel per subset, families as horizontal bars.
4. **Coverage matrix** — families × subsets, cells shaded by the share scored.
5. **Method notes** — the taxonomy rules, the exact filters used (and any server
   quirks worked around), and the reconciliation totals. This is not boilerplate:
   without it nobody can reproduce or trust the counts.

## Scale the panels independently

The subsets differ by an order of magnitude (893 / 525 / 69 in the August 2026
snapshot). On a shared axis the smallest subset is invisible. Give each panel its
own maximum, label it (`axis max 387`), and say in the surrounding text that shapes
compare across panels while absolute numbers are read off the labels. A log axis
would technically fit all three but bar length stops meaning magnitude, which is
worse.

## Two overlapping metrics, not four categories

OP-scored and FF-scored overlap — a trajectory can have either, both, or neither.
Resist stacking them into segments; a reader will add the segments. A matrix with
one cell per metric, each shaded by its own share, keeps them visibly separate.
Say explicitly in the notes that the columns must not be summed, and give the
both-scored count so the union can be worked out.

Shade with a single sequential hue as an alpha over the surface
(`rgba(var(--accent-rgb), 0.06 + 0.52 * share)`) rather than fixed ramp steps —
it composites correctly in both themes with one definition, and the text token
stays legible over every step.

## Distinguish "no data" from "zero"

A family absent from a subset (`n = 0`) is not the same as a family present but
never scored. Render the first as `–` and the second as `0`. AMOEBA has no POPC
trajectories at all; ECC-lipids has 60 POPC trajectories and 2 OP scores. Collapsing
those into the same glyph loses the more interesting of the two facts.

## Assert the totals in the page

Put the reconciliation into a `console.error` guard in the page's own script:

```js
SETS.forEach(s => {
  const t = FAM.reduce((a,f) => [a[0]+f[s.key][0], a[1]+f[s.key][1], a[2]+f[s.key][2]], [0,0,0]);
  if (t[0]!==s.n || t[1]!==s.op || t[2]!==s.ff) console.error("column mismatch", s.key, t);
});
```

Three lines, and a later edit that breaks a number fails loudly in the console
instead of shipping a wrong chart quietly. Screenshot the page and check the
console output before publishing.

## Writing the findings

The temptation is to name a winner. The more useful framing is the trade-off plus
its denominator: which observable each force field is good at, how many scored
trajectories that judgement rests on, and where the within-force-field spread
swamps the between-force-field difference. Readers who want a single name will
still get one from a sentence like "best all-round for POPC on the 8 scored
OPLS trajectories, with the caveat that three of them have implausible areas per
lipid" — and that sentence is defensible, where a bare ranking is not.
