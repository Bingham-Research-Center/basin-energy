/*
 * Research visualizations are shared by the Admin and regular User
 * dashboards. Load this bundle ONLY on actual research pages, after
 * their HTML and any page-provided inline configuration / Leaflet scripts.
 *
 * Previously these modules were copied into both frontend.js and
 * backend.js. The frontend layout loads BOTH bundles, causing duplicate
 * initialization and Chart.js canvas ownership conflicts.
 */
import './frontend/emission-trends';
import './frontend/produced-water';
import './frontend/carbon-mapper-map';
import './frontend/subsurface-natural-gas-leaks';
import './frontend/oil-well-pad-emissions';
