# Diagram Visual Verification

The generated `architecture.png` and `runtime-flow.png` were visually inspected.

`architecture.png` rendered at 2752×2192 with readable node labels and arrows. It clearly shows browser-to-web/API entry points, controllers, services, Eloquent models, database, views, logging, and GitHub Actions. No clipped labels or rendering artifacts were observed.

`runtime-flow.png` rendered at 3120×1212 with readable participant labels and event arrows. It clearly shows click tracking followed by conversion reporting, conversion/commission persistence, cashback credit, and referral-point credit. No clipped labels or rendering artifacts were observed.

`auth-flow.png` rendered at 3120×868 with readable labels and clearly communicates the custom session path, Laravel guard path, and missing `Auth::login` bridge.

`database.png` rendered at 3120×1832 with readable entity labels and relationship connectors. The diagram is wide but not clipped; the tracking chain and product/reward relationships are visible.
