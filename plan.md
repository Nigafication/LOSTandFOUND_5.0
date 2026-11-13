# LOST & FOUND redesign plan

## Implementation approach

Transform the starter Next.js page into a responsive, single-page campus lost-and-found experience. The page will use local sample data for an immediately useful interface, client-side search and category filters, saved-item interactions, a compact reporting modal, and a trustworthy information footer. No backend behavior is invented; the UI is structured so API wiring can be added later.

## Project structure

- `frontend/app/page.tsx`: Client-side dashboard UI, item data, filtering, saved state, and report dialog.
- `frontend/app/globals.css`: Brand tokens, responsive layout, component styling, interaction states, and motion.
- `frontend/app/layout.tsx`: Metadata and font setup.
- `frontend/public/manus-routes.json`: Route manifest for the single-page experience.

## Design direction

- **Design Movement**: Civic digital service meets editorial campus publication (0.06); quiet Swiss utility (0.04); soft neo-brutalist campus board (0.08). Chosen: civic editorial utility.
- **Core Principles**: Make the next action obvious; use generous whitespace and clear grouping; balance official trust with warmth; keep scanning faster than reading.
- **Color Philosophy**: Deep ink and UCLA-adjacent blue create authority. A high-energy gold accent signals action and highlights the most important moments. Warm paper tones prevent the service from feeling sterile.
- **Layout Paradigm**: An editorial masthead and split hero lead into a filter rail plus asymmetric result feed, instead of a generic centered SaaS dashboard.
- **Signature Elements**: Small uppercase section labels, gold rule marks, and item cards with colored status rails.
- **Interaction Philosophy**: Every control should explain what changes. Search filters live above results, save is one tap, and report actions open a focused dialog without leaving the page.
- **Animation**: Use short ease-out fades and 3px lifts on cards/buttons. Respect reduced-motion preferences.
- **Typography System**: Geist for interface text, with tight heavy display headings and relaxed readable body copy.
- **Brand Essence**: A calm, campus-wide way to reunite people with their things. Trustworthy, helpful, human.
- **Brand Voice**: Direct and reassuring. Example lines: “Your missing item might be here.” and “Describe it. We’ll help get it home.”
- **Wordmark & Logo**: A compact square mark with an offset gold dot, paired with a two-line wordmark.
- **Signature Brand Color**: `#1f4b86` campus blue.

## Required outcomes

- Replace the starter screen with a finished lost-and-found landing/search experience.
- Add responsive navigation, hero copy, search, category filters, result cards, saved-item interactions, and report-item dialog.
- Add trust and process information modeled after the reference site’s official clarity.
- Keep the route set synchronized and ensure the frontend builds cleanly.
