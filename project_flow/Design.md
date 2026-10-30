# Lukani — Design System Specification

> Plant & Flower Shop eCommerce HTML Template by HasTech (HasThemes) · ThemeForest item 25142862

---

## 0. Read this first: provenance and confidence

The live preview (`preview.themeforest.net/.../full_screen_preview/25142862`) disallows automated access, so **its rendered CSS, fonts, spacing and section layouts were NOT read directly.** This document is built from three sources:

1. The author's ThemeForest item page (features, page counts, stack, changelog).
2. The author's Dribbble listing (the 7-colour palette).
3. Values calculated from the above (contrast ratios, tints).

Everything else is a reasoned inference or a recommendation. Every section and table is tagged so you never mistake a guess for a fact:

| Tag | Meaning |
|---|---|
| **[V]** | Verified from the author's listing or the framework's documented defaults |
| **[C]** | Computed from verified values (math, not opinion) |
| **[I]** | Inferred from the stack/category. Likely, but check against the live CSS |
| **[P]** | Proposed. A recommendation to fill a gap, not a measurement of the template |

Section 15 gives a 10-minute DevTools checklist to turn every **[I]** and **[P]** into **[V]**.

---

## 1. Product facts

| Item | Value | Tag |
|---|---|---|
| Name | Lukani – Plant and Flower Shop eCommerce HTML Template | V |
| Author | HasTech (HasThemes), Elite Author | V |
| Category | Site Templates → Retail → Shopping | V |
| Price / sales | $17 regular license, ~198 sales | V |
| Latest version | 2.0.3 (5 July 2021) | V |
| Framework | Bootstrap 5.0.x (original 2020 release was Bootstrap 4; upgraded in 2.0.3) | V |
| Markup / styles | HTML5, CSS3, SASS source, W3C-valid markup | V |
| Sliders | owl-carousel and slick slider | V |
| Icons | Font Awesome and Bicon | V |
| Fonts | "Google Font" (family name not published in the listing) | V / name unknown |
| Files included | HTML, CSS, JS, PHP (PHP is for the contact and Mailchimp forms) | V / purpose I |
| Images | **Not included** in the download | V |
| Browsers | Firefox, Safari, Chrome, Opera, Edge, IE11 | V |
| Scope | Gardening, houseplants, landscaping, nursery, flower shop | V |

**Version history relevant to design [V]**

| Version | Design-relevant change |
|---|---|
| 2.0.0 (May 2020) | Off-canvas mini cart added; categories menu fixed; responsive CSS fixes |
| 2.0.1 | JS fix |
| 2.0.2 (Jul 2020) | New home version; Instagram newsfeed section added |
| 2.0.3 (Jul 2021) | Bootstrap 5.0 upgrade; responsive improvements |

---

## 2. Design principles

*All [I]: derived from the category, palette and feature set.*

1. **Botanical calm.** Muted, desaturated greens on a warm blush-grey ground. Nothing neon or saturated. The product photography carries the colour; the UI recedes.
2. **Photography first.** Plants and flowers are the hero. Cards, banners and sliders are image-led with light text treatment.
3. **Conventional e-commerce clarity.** Familiar patterns (mega/category menu, off-canvas cart, grid/list toggle, quick product tiles). Shoppers should never have to learn the UI.
4. **Generous air.** Plant imagery needs breathing room. Wide section padding, restrained density, short text blocks.
5. **One strong action colour.** Deep forest green is the single primary action colour. Lighter greens are supporting accents only.
6. **Content-agnostic shell.** Because images are not bundled, layouts must tolerate varied crops. Use fixed aspect-ratio image wrappers.

---

## 3. Colour

### 3.1 Brand palette [V]

Seven colours published by the author:

| Token | Hex | RGB | Suggested name |
|---|---|---|---|
| `--lk-blush` | `#F3EAEB` | 243, 234, 235 | Blush Mist |
| `--lk-sage` | `#C5C4B4` | 197, 196, 180 | Sage Stone |
| `--lk-ink` | `#12190C` | 18, 25, 12 | Garden Ink |
| `--lk-grey` | `#63635E` | 99, 99, 94 | Stone Grey |
| `--lk-forest` | `#3C521B` | 60, 82, 27 | Forest |
| `--lk-leaf` | `#95A264` | 149, 162, 100 | Leaf |
| `--lk-moss` | `#7B825B` | 123, 130, 91 | Moss |

*The hex values are verified. The role assignments below are [I]/[P].*

### 3.2 Role mapping [I]

| Role | Token | Hex | Used for |
|---|---|---|---|
| Page background | blush | `#F3EAEB` | Body / alternating section backgrounds |
| Surface | white | `#FFFFFF` | Cards, header, modals, inputs |
| Heading & body text | ink | `#12190C` | H1–H6, primary copy, dark footer background |
| Muted text | grey | `#63635E` | Meta, captions, breadcrumbs, placeholder text, old price |
| Primary action | forest | `#3C521B` | Primary buttons, links, active nav, focus ring |
| Secondary accent | moss | `#7B825B` | Secondary buttons (outlined), badges, icon accents, hover underline |
| Highlight accent | leaf | `#95A264` | Decorative fills, tags, slider dots, section dividers, hover on dark |
| Borders & dividers | sage | `#C5C4B4` | 1px borders, table rules, input borders, skeletons |

### 3.3 Derived tints and shades [C]

Mixed with white (tint) or black (shade):

| Base | 80% white | 60% white | 40% white | 20% white | 15% black | 30% black |
|---|---|---|---|---|---|---|
| Forest | `#D8DCD1` | `#B1BAA4` | `#8A9776` | `#637549` | `#334617` | `#2A3913` |
| Leaf | `#EAECE0` | `#D5DAC1` | `#BFC7A2` | `#AAB583` | `#7F8A55` | `#687146` |
| Moss | `#E5E6DE` | `#CACDBD` | `#B0B49D` | `#959B7C` | `#696E4D` | `#565B40` |

**State colours [P]**

| State | Colour |
|---|---|
| Primary hover | `#334617` (forest, 15% darker) |
| Primary active/pressed | `#2A3913` (forest, 30% darker) |
| Primary tint background (selected row, light chip) | `#D8DCD1` |
| Focus ring | `0 0 0 3px rgba(60,82,27,.30)` |
| Disabled | background `#C5C4B4`, text `#63635E` |

**Semantic colours [P]**: the palette has no verified error/warning colours. Suggested earthy options that sit with the greens (re-check contrast before use):

| Semantic | Hex |
|---|---|
| Success | `#3F7D3A` |
| Warning / low stock | `#B7791F` |
| Danger / out of stock | `#B3382C` |
| Info | `#3B6E8F` |

### 3.4 Colour usage rules [P]

- Primary buttons are always **forest on white/blush**, never leaf or moss, because contrast (see §13).
- **Leaf is decorative only.** It must not carry normal-size text on white.
- Don't put grey text on sage backgrounds.
- Keep a roughly 60/30/10 split: 60% blush/white surfaces, 30% ink/grey type, 10% green accents.
- Use the dark footer (ink) to anchor the page; text on it is blush, links hover to leaf.

---

## 4. Typography

**The Google Font family names used by Lukani are not published in the listing [V], so the families below are [P].** Replace them with the real values once you inspect the live CSS (§15).

### 4.1 Font stacks [P]

| Role | Recommended | Fallback stack |
|---|---|---|
| Headings | Playfair Display (500/600) for an elegant florist feel, or a clean sans if the live site uses one | `Georgia, "Times New Roman", serif` |
| Body / UI | Jost or DM Sans (400/500/600) | `-apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif` |
| Prices / numerals | Same as body, `font-variant-numeric: tabular-nums` | n/a |

### 4.2 Type scale [P]

| Style | Desktop | Mobile | Weight | Line-height | Letter-spacing | Use |
|---|---|---|---|---|---|---|
| Display | 56px / 3.5rem | 36px | 600 | 1.1 | -0.01em | Hero slider headline |
| H1 | 44px / 2.75rem | 32px | 600 | 1.2 | -0.01em | Page titles |
| H2 | 36px / 2.25rem | 28px | 600 | 1.25 | 0 | Section headings |
| H3 | 28px / 1.75rem | 24px | 600 | 1.3 | 0 | Sub-sections, product title (single) |
| H4 | 22px / 1.375rem | 20px | 600 | 1.35 | 0 | Card titles, widget titles |
| H5 | 18px / 1.125rem | 17px | 600 | 1.4 | 0 | Product name in cards |
| Body L | 18px | 17px | 400 | 1.7 | 0 | Intro paragraphs |
| Body | 16px / 1rem | 16px | 400 | 1.625 | 0 | Default copy (Bootstrap base is 1rem) |
| Small | 14px | 14px | 400 | 1.55 | 0 | Meta, descriptions in lists |
| Caption | 12px | 12px | 500 | 1.5 | 0.02em | Badges, legal, breadcrumbs |
| Button | 14px | 14px | 600 | 1 | 0.06em, uppercase | All buttons |
| Nav link | 15px | 16px | 500 | 1 | 0.02em | Primary navigation |
| Price | 18–20px | 18px | 600 | 1 | 0 | Product price (forest); old price grey, strikethrough |

### 4.3 Text rules [P]

- Max line length 65–75 characters for body copy (~680px).
- Headings use ink; section subtitles use grey.
- Links in body copy: forest, underlined on hover.
- Product names truncate at 2 lines (`-webkit-line-clamp: 2`).

---

## 5. Layout and grid

### 5.1 Bootstrap 5.0 defaults [V: framework-documented, may be overridden by the theme]

| Breakpoint | Min width | Container max-width |
|---|---|---|
| xs | <576px | 100% |
| sm | ≥576px | 540px |
| md | ≥768px | 720px |
| lg | ≥992px | 960px |
| xl | ≥1200px | 1140px |
| xxl | ≥1400px | 1320px |

- 12-column grid, default gutter 1.5rem (24px) [V].
- Themes in this family often widen the container for the header and hero (`container-fluid` or custom 1200–1400px). **Confirm on the live site [I].**

### 5.2 Page scaffolding [I]

```
┌────────────────────────────────────────────┐
│ Top bar: language / currency / account     │
├────────────────────────────────────────────┤
│ Header: logo · search · wishlist · cart    │
│ Main nav + categories menu                 │
├────────────────────────────────────────────┤
│ Page body (sections stacked)               │
├────────────────────────────────────────────┤
│ Newsletter (Mailchimp) strip               │
│ Footer: columns + payment + copyright      │
└────────────────────────────────────────────┘
```

### 5.3 Grids by page type [I]

| Page | Desktop | Tablet | Mobile |
|---|---|---|---|
| Shop grid | 3–4 cols (+ left sidebar variant) | 2–3 | 2 (or 1) |
| Shop list | 1 col, image left / content right | 1 | 1 stacked |
| Single product | 2 cols: gallery 50% / info 50% | stacked | stacked |
| Blog | 8/4 split (content + sidebar), or grid | 1 | 1 |
| Footer | 4 columns | 2 | 1 |

---

## 6. Spacing, radius, elevation

### 6.1 Spacing scale

Bootstrap 5.0 spacers [V]: `0 · 0.25rem · 0.5rem · 1rem · 1.5rem · 3rem`.

Proposed extended 4px-based scale [P]:

| Token | Value |
|---|---|
| `--lk-space-1` | 4px |
| `--lk-space-2` | 8px |
| `--lk-space-3` | 12px |
| `--lk-space-4` | 16px |
| `--lk-space-5` | 24px |
| `--lk-space-6` | 32px |
| `--lk-space-7` | 48px |
| `--lk-space-8` | 64px |
| `--lk-space-9` | 96px |

**Section rhythm [P]:** vertical padding 96px desktop / 64px tablet / 48px mobile. Gap between a section heading block and its content: 40px. Card inner padding 16–24px.

### 6.2 Radius [P]

| Token | Value | Use |
|---|---|---|
| `--lk-radius-sm` | 2–4px | Buttons, inputs |
| `--lk-radius-md` | 8px | Cards, images, dropdowns |
| `--lk-radius-pill` | 999px | Badges, chips, quantity pill |
| `--lk-radius-circle` | 50% | Round icon buttons (wishlist, quick view), slider arrows |

*Check live: many templates in this family use square corners (0) for buttons and images.*

### 6.3 Elevation [P]

| Level | Shadow | Use |
|---|---|---|
| 0 | none | Flat cards with 1px sage border |
| 1 | `0 2px 8px rgba(18,25,12,.06)` | Resting cards |
| 2 | `0 8px 24px rgba(18,25,12,.10)` | Card hover, dropdowns, mega menu |
| 3 | `0 16px 48px rgba(18,25,12,.16)` | Off-canvas cart, modals, newsletter popup |

Shadows are tinted with ink (`#12190C`), not pure black, to keep the greens clean.

---

## 7. Iconography [V stack / P usage]

- **Font Awesome** for general UI icons and social logos; **Bicon** for the e-commerce set (cart, bag, heart, search, compare) [V].
- Size: 16px inline, 20px header actions, 24px feature icons. Stroke/fill colour: ink by default, forest on hover/active.
- Header action icons carry a small count badge (cart, wishlist): 18px circle, forest background, white 11px numeral [P].

---

## 8. Components

*Tags: the existence of each component is [V] or [I] as marked; exact dimensions are [P] unless stated.*

### 8.1 Buttons

| Variant | Background | Text | Border | Hover | Tag |
|---|---|---|---|---|---|
| Primary | forest | white | forest | bg `#334617` | P |
| Secondary / outline | transparent | forest | 1px forest | bg forest, text white | P |
| Ghost / text | transparent | ink | none | text forest, underline | P |
| On-dark | blush | ink | blush | bg leaf | P |

- Height 44px (default), 36px (small), 52px (large). Horizontal padding 24–32px. Uppercase, 600 weight, 0.06em tracking.
- Transition: `background-color .25s, color .25s, border-color .25s, transform .25s`.
- Icon-only round buttons: 40px circle, white background, 1px sage border, ink icon, hover forest/white.
- Focus-visible: 3px forest ring at 30% opacity, 2px offset.

### 8.2 Top bar [I: language and currency switchers are V]

- Slim strip (~40px), blush or ink background.
- Left: promo / contact line. Right: **language dropdown, currency dropdown**, account/login links [V: multi-language and multi-currency are advertised features].
- 13px text, grey on blush (or blush on ink).

### 8.3 Header and navigation [I]

- Logo left; search centre or right; wishlist, account, cart icons right.
- Main nav: Home, Shop, Pages, Blog, Contact (plus **categories menu**, whose behaviour was fixed in 2.0.0 [V]).
- Dropdowns/mega menu: white surface, radius-md, elevation 2, 12–16px item padding, hover colour forest.
- Sticky header on scroll with reduced height (~64px) and elevation 1 [I].
- Mobile: hamburger opens an off-canvas menu; cart opens the off-canvas mini cart.

### 8.4 Off-canvas mini cart [V: added in 2.0.0]

- Slides from the right, ~360–420px wide, white surface, elevation 3, dark overlay `rgba(18,25,12,.5)`.
- Item row: 72px thumbnail, name (H5), qty × price (forest), remove (×) icon in grey.
- Footer: subtotal, primary "Checkout" button, outline "View cart" button.

### 8.5 Hero slider [I: owl-carousel/slick V]

- Full-width image slides with left-aligned text block: eyebrow (caption, moss), Display headline (ink or white depending on image), body L, primary button.
- Controls: round arrows (40px) appearing on hover, pagination dots (8px, sage → forest active).
- Slide transition: fade or slide 600–800ms ease; autoplay 5–6s with pause on hover.

### 8.6 Product card [I]

Anatomy: image wrapper (fixed aspect ratio, 1:1 or 4:5) → badge(s) → hover action cluster → category (caption, grey) → name (H5) → rating → price row.

| Part | Spec |
|---|---|
| Image | `object-fit: cover`; second image may crossfade on hover |
| Badges | Pill, 12px caps; "New" = forest/white, "Sale -X%" = danger or leaf/ink |
| Hover actions | Vertical or horizontal icon cluster: add to cart, wishlist, compare, quick view; round white buttons, slide up/fade in 250ms |
| Name | 2-line clamp, ink, hover forest |
| Rating | 5 stars, 14px, leaf or amber fill |
| Price | Current in forest 600; original in grey with strikethrough |
| Card | white, 1px sage border or none; hover elevation 2 and 4px lift |

### 8.7 Shop layouts [V: grid and list, 6+ styles]

- Toolbar: grid/list view toggle, result count, sort select, items-per-page.
- Sidebar widgets (some styles): categories, price range slider, colour/size filters, tags, featured products.
- List view: image 30–35% / content 65–70%, with short description and visible action buttons.
- Pagination: square or round 40px items, active forest/white, hover tint.

### 8.8 Single product [V: 4+ styles]

- Gallery: large image with thumbnail strip (slick slider), optional zoom.
- Info: title (H3), rating, price, short description, quantity stepper, "Add to cart" primary, wishlist/compare links, SKU/category/tags meta, share icons.
- Tabs below: Description / Additional info / Reviews. Active tab: forest text with 2px bottom border.
- "Related products" carousel (owl-carousel).

### 8.9 Forms

| Element | Spec |
|---|---|
| Inputs / selects / textarea | Height 48px, 1px sage border, white background, radius-sm, 16px text |
| Placeholder | grey at 80% |
| Focus | border forest + 3px ring at 30% |
| Error | border danger, 13px message below |
| Labels | 14px, 500, ink, 8px below |
| Quantity stepper | − / input / + in a single 44–48px high bordered group |
| Contact form | Dynamic (validated, submits via server-side handler) [V] |
| Newsletter | Email + button inline; Mailchimp-backed [V] |

### 8.10 Newsletter popup [V]

- Modal on first visit: split layout (image + form) or centered card, radius-md, elevation 3, close (×) top-right, "Don't show again" checkbox [I].

### 8.11 Instagram feed [V: added in 2.0.2]

- Grid of 6 square tiles (or a row) beneath the main content; hover overlay in `rgba(60,82,27,.6)` with a white Instagram icon [P].

### 8.12 Blog [V: 4 blog pages]

- Card: image (16:10), meta row (date, author, comments: caption, grey), title (H4), 2–3 line excerpt, "Read more" text link in forest.
- Post page: single column, 720px max reading width, blockquote with 3px leaf left border.

### 8.13 Footer [I]

- Ink background, blush text. Four columns: about + social, information links, account links, contact/newsletter.
- Bottom bar: copyright left, payment-method icons right, 1px divider at 12% blush.
- Link hover: leaf.

### 8.14 Other components [I]

Breadcrumb banner (page title over an image or blush band), cart table, checkout (billing + order summary), wishlist and compare tables, accordion (FAQ), 404 page, back-to-top button (round, forest).

---

## 9. Page inventory

**Verified counts [V]:** 28+ HTML pages · 4 unique home versions · 6+ shop styles (grid and list) · 4+ single-product styles · 4 blog pages.

**Expected page set [I]:** confirm against the download:

| Group | Pages |
|---|---|
| Home | Home 1, 2, 3, 4 |
| Shop | Grid (with/without sidebar, 3/4 columns), List, Full-width |
| Product | Single product (several gallery/info layouts) |
| Commerce | Cart, Checkout, Wishlist, Compare, My account / Login-Register |
| Content | About, Contact, FAQ, 404 |
| Blog | Blog grid, Blog list, Blog details, Blog sidebar variant |

---

## 10. Interaction and motion

| Behaviour | Spec | Tag |
|---|---|---|
| Carousels | owl-carousel (hero, brands, related products), slick (product gallery thumbs) | V |
| Transitions | 200–300ms `ease` for colour/shadow; 600–800ms for slides | P |
| Card hover | translateY(-4px) + elevation 2 + reveal action icons | I |
| Off-canvas | translateX(100% → 0), 300ms `cubic-bezier(.4,0,.2,1)` | P |
| Dropdowns | fade + 8px upward slide, 200ms | P |
| Scroll | Sticky header; back-to-top button after ~300px | I |

**Reduced motion [P]:**
```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}
```

---

## 11. Imagery guidelines [P]

Because the download ships without images, define the visual standard yourself:

- **Subjects:** single hero plant on a neutral backdrop for product shots; lifestyle shots (plants in rooms) for banners.
- **Palette:** natural greens, terracotta, cream; avoid saturated filters. Backgrounds in blush/sage tones to match the UI.
- **Ratios:** product 1:1 or 4:5; hero 16:7 desktop (crop-safe for mobile 4:5); blog 16:10; Instagram 1:1.
- **Sizes:** product 800×800 (retina-ready 1600 for zoom); hero 1920×820; export WebP with JPEG fallback; lazy-load below the fold.
- **Alt text:** common name + variety + context ("Monstera deliciosa in a terracotta pot").

---

## 12. Responsive behaviour

*Bootstrap breakpoints [V]; specifics [I]/[P].*

| Breakpoint | Behaviour |
|---|---|
| ≥1200px | Full header with inline nav; 3–4 product columns; sidebar visible |
| 992–1199px | Same, container narrower (960px) |
| 768–991px | Collapsed nav (hamburger); 2–3 product columns; sidebar moves above or into an off-canvas filter |
| 576–767px | 2 product columns; single-column content; stacked footer |
| <576px | 1–2 product columns; hero text reduces to ~36px; buttons become full-width in forms; touch targets ≥44px |

---

## 13. Accessibility

### 13.1 Contrast matrix [C]

Calculated WCAG 2.x contrast ratios from the verified palette. Thresholds: 4.5:1 AA normal text, 3:1 large text and UI components, 7:1 AAA.

| Foreground | Background | Ratio | Result |
|---|---|---|---|
| Ink `#12190C` | White | 17.95 | AAA |
| Ink | Blush `#F3EAEB` | 15.20 | AAA |
| Ink | Sage `#C5C4B4` | 10.20 | AAA |
| Forest `#3C521B` | White | 8.70 | AAA |
| White | Forest | 8.70 | AAA |
| Forest | Blush | 7.36 | AAA |
| Blush | Forest | 7.36 | AAA |
| Stone Grey `#63635E` | White | 6.04 | AA |
| Stone Grey | Blush | 5.11 | AA |
| Forest | Sage | 4.94 | AA |
| Leaf `#95A264` | Ink | 6.52 | AA |
| Moss `#7B825B` | Ink | 4.43 | Large/UI only (just under 4.5) |
| Moss | White | 4.05 | Large/UI only |
| White | Moss | 4.05 | Large/UI only |
| Stone Grey | Sage | 3.43 | Large/UI only |
| Leaf | Forest | 3.16 | Large/UI only |
| Leaf | White | 2.75 | **Fail** |
| White | Leaf | 2.75 | **Fail** |

### 13.2 Rules derived from the matrix

- **Never** set white text on leaf, or leaf text on white. Use ink on leaf instead (a **[C]** pairing that passes).
- White-on-moss buttons fail AA for normal-size text. Use forest for filled buttons; reserve moss for outlines, icons, and large type.
- Grey meta text is fine on white/blush but not on sage.
- Leaf on ink (dark footer hover/accent) passes AA.

### 13.3 Other requirements [P]

- Visible focus on every interactive element (forest ring); never `outline: none` without replacement.
- Carousels: pause on hover/focus, keyboard-operable arrows, `aria-label`s on dots.
- Icon-only buttons need `aria-label` (wishlist, compare, quick view, cart, close).
- Off-canvas cart and popup: trap focus, close on Esc, return focus to trigger.
- Don't convey sale/stock state by colour alone. Pair with text.
- Respect `prefers-reduced-motion` (§10).

---

## 14. Design tokens

### 14.1 CSS custom properties [V colours / P everything else]

```css
:root {
  /* Brand palette (verified) */
  --lk-blush:  #F3EAEB;
  --lk-sage:   #C5C4B4;
  --lk-ink:    #12190C;
  --lk-grey:   #63635E;
  --lk-forest: #3C521B;
  --lk-leaf:   #95A264;
  --lk-moss:   #7B825B;
  --lk-white:  #FFFFFF;

  /* Roles (proposed) */
  --lk-bg:            var(--lk-blush);
  --lk-surface:       var(--lk-white);
  --lk-text:          var(--lk-ink);
  --lk-text-muted:    var(--lk-grey);
  --lk-primary:       var(--lk-forest);
  --lk-primary-hover: #334617;
  --lk-primary-press: #2A3913;
  --lk-primary-tint:  #D8DCD1;
  --lk-accent:        var(--lk-leaf);
  --lk-accent-2:      var(--lk-moss);
  --lk-border:        var(--lk-sage);

  /* Semantic (proposed) */
  --lk-success: #3F7D3A;
  --lk-warning: #B7791F;
  --lk-danger:  #B3382C;
  --lk-info:    #3B6E8F;

  /* Type (proposed; replace with live values) */
  --lk-font-heading: "Playfair Display", Georgia, serif;
  --lk-font-body: "Jost", -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

  /* Radius / shadow / motion (proposed) */
  --lk-radius-sm: 4px;
  --lk-radius-md: 8px;
  --lk-radius-pill: 999px;
  --lk-shadow-1: 0 2px 8px rgba(18,25,12,.06);
  --lk-shadow-2: 0 8px 24px rgba(18,25,12,.10);
  --lk-shadow-3: 0 16px 48px rgba(18,25,12,.16);
  --lk-ease: cubic-bezier(.4, 0, .2, 1);
  --lk-dur: .25s;
}
```

### 14.2 Bootstrap 5 / SASS overrides [P]

Since the template ships SASS and Bootstrap 5, theme the framework before compiling:

```scss
$primary:   #3C521B;
$secondary: #7B825B;
$success:   #3F7D3A;
$warning:   #B7791F;
$danger:    #B3382C;
$info:      #3B6E8F;
$light:     #F3EAEB;
$dark:      #12190C;

$body-bg:       #F3EAEB;
$body-color:    #12190C;
$border-color:  #C5C4B4;
$link-color:    #3C521B;
$link-hover-color: #334617;

$font-family-sans-serif: "Jost", -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
$headings-font-family:   "Playfair Display", Georgia, serif;
$headings-font-weight:   600;
$headings-color:         #12190C;

$btn-border-radius: 4px;
$btn-padding-y: .7rem;
$btn-padding-x: 1.75rem;
$btn-font-weight: 600;
$input-border-color: #C5C4B4;
$input-focus-border-color: #3C521B;
$input-focus-box-shadow: 0 0 0 .2rem rgba(60,82,27,.3);
$enable-shadows: true;
```

---

## 15. Closing the gaps: verification checklist

Open the live preview in your browser (this takes about 10 minutes) and replace every **[I]/[P]** with a measured value:

1. **Fonts.** DevTools → Elements → select `<body>` and an `<h1>` → Computed → `font-family`, `font-size`, `font-weight`, `line-height`. Also check the Network tab for `fonts.googleapis.com` requests to see the exact family names.
2. **Colours in use.** In the Styles pane, look for `:root` variables or search the CSS for the 7 hex values. Note which colour each button, link, badge and background actually uses.
3. **Container width.** Select `.container` → Computed → `max-width`. Compare to §5.1.
4. **Header.** Measure height (normal and sticky), logo size, nav font-size, dropdown padding and shadow.
5. **Product card.** Note image ratio, hover behaviour, which action icons appear, badge style, price styling, border/radius/shadow.
6. **Buttons and inputs.** Computed `height`, `padding`, `border-radius`, `text-transform`, `letter-spacing`, hover colours.
7. **Section spacing.** Select a few sections → Computed → `padding-top` / `padding-bottom`.
8. **Breakpoints.** Use the responsive toolbar at 1400 / 1200 / 992 / 768 / 576 / 375 and note layout changes.
9. **Motion.** In Styles, search for `transition`, `animation`, `@keyframes`; record durations and easings.
10. **Page list.** Open the nav and note every page name to replace the expected set in §9.

**Shortcut:** if you buy or already own the template, the unzipped `assets/css/style.css` (and the SASS partials) plus a few screenshots would let this document be rewritten with every value verified. Paste the CSS or upload the zip/screenshots and the **[I]/[P]** tags can be turned into **[V]**.

---

## 16. Licence note

The template is sold under Envato's Regular/Extended licence (single end product). This document describes design values for reference and re-implementation; it does not reproduce template code, and images are not included with the template.
