<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Query active submissions for an instance, aggregate, and render the report.
 * Caller is responsible for the capability gate and for exit().
 */
function nb_discovery_output_report( $instance ) {
    global $wpdb;
    $table = nb_discovery_table_name();
    $slug  = $instance['slug'];

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, respondent_name, respondent_email, payload, created_at, excluded
             FROM {$table} WHERE instance = %s ORDER BY id ASC",
            $slug
        ),
        ARRAY_A
    );
    if ( ! is_array( $rows ) ) { $rows = array(); }

    $submissions   = array();
    $excluded_rows = array();
    foreach ( $rows as $r ) {
        if ( (int) $r['excluded'] === 1 ) {
            $excluded_rows[] = array( 'id' => (int) $r['id'], 'name' => $r['respondent_name'], 'created_at' => $r['created_at'] );
            continue;
        }
        $payload = json_decode( $r['payload'], true );
        if ( ! is_array( $payload ) ) { $payload = array(); }
        $submissions[] = array(
            'id'    => (int) $r['id'],
            'name'  => $r['respondent_name'],
            'email' => $r['respondent_email'],
            'payload' => $payload,
        );
    }

    $aggregate = nb_discovery_aggregate( $submissions, $instance );
    nb_discovery_render_report( $instance, $aggregate, $excluded_rows );
}

/**
 * Echo the standalone branded report document. No DB access here.
 */
function nb_discovery_render_report( $instance, $aggregate, $excluded_rows = array() ) {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
    nocache_headers();

    $ver_css = newblood_asset_version( '/assets/css/discovery-report.css' );
    $css_uri = get_template_directory_uri() . '/assets/css/discovery-report.css?v=' . $ver_css;
    $client  = $instance['client_name'];
    $n       = (int) $aggregate['count'];
    ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Discovery report — <?php echo esc_html( $client ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( $css_uri ); ?>">
</head>
<body class="nb-r-body">
<main class="nb-r-shell">

  <header class="nb-r-head">
    <p class="nb-r-eyebrow">Combined discovery</p>
    <h1><?php echo esc_html( $client ); ?><span class="nb-r-dot">.</span></h1>
    <p class="nb-r-roster"><strong><?php echo esc_html( $n ); ?></strong> stakeholder<?php echo $n === 1 ? '' : 's'; ?>:
      <?php
      $names = array();
      foreach ( $aggregate['respondents'] as $r ) { $names[] = esc_html( $r['name'] ); }
      echo implode( ' &middot; ', $names );
      ?>
    </p>
  </header>

  <?php if ( $n === 0 ) : ?>
    <section class="nb-r-section"><p class="nb-r-empty">No responses yet for this instance.</p></section>
  <?php else : ?>

  <section class="nb-r-section">
    <h2>Priority &amp; gap map<span class="nb-r-dot">.</span></h2>
    <p class="nb-r-sub">Averaged across stakeholders, ranked by the biggest gap between how important a capability is and how well it's handled today.</p>
    <?php foreach ( $aggregate['services'] as $s ) : ?>
      <div class="nb-r-svc<?php echo $s['split'] ? ' is-split' : ''; ?>">
        <div class="nb-r-svc-top">
          <span class="nb-r-svc-label"><?php echo esc_html( $s['label'] ); ?></span>
          <?php if ( $s['mean_gap'] !== null ) : ?>
            <span class="nb-r-gap">gap <?php echo esc_html( $s['mean_gap'] ); ?></span>
          <?php else : ?>
            <span class="nb-r-gap nb-r-gap-na">not rated</span>
          <?php endif; ?>
        </div>
        <div class="nb-r-bars">
          <div class="nb-r-bar"><span class="nb-r-bar-cap">Importance</span>
            <span class="nb-r-track"><span class="nb-r-fill nb-r-fill-imp" style="width:<?php echo esc_attr( $s['mean_importance'] * 10 ); ?>%"></span></span>
            <span class="nb-r-num"><?php echo esc_html( $s['mean_importance'] ); ?></span>
          </div>
          <?php if ( $s['mean_handling'] !== null ) : ?>
          <div class="nb-r-bar"><span class="nb-r-bar-cap">Handled today</span>
            <span class="nb-r-track"><span class="nb-r-fill nb-r-fill-now" style="width:<?php echo esc_attr( $s['mean_handling'] * 10 ); ?>%"></span></span>
            <span class="nb-r-num"><?php echo esc_html( $s['mean_handling'] ); ?></span>
          </div>
          <?php endif; ?>
        </div>
        <?php if ( $s['split'] && $s['high'] && $s['low'] ) : ?>
          <p class="nb-r-split">⚑ Team split — <?php echo esc_html( $s['high']['name'] ); ?> rates this <?php echo esc_html( $s['high']['score'] ); ?>/10, <?php echo esc_html( $s['low']['name'] ); ?> rates it <?php echo esc_html( $s['low']['score'] ); ?>/10.</p>
        <?php endif; ?>
        <details class="nb-r-detail"><summary>Per stakeholder</summary>
          <ul>
            <?php foreach ( $s['per_respondent'] as $p ) : ?>
              <li><?php echo esc_html( $p['name'] ); ?>: importance <?php echo esc_html( $p['importance'] ); ?><?php if ( $p['handling'] !== null ) { echo ', handled ' . esc_html( $p['handling'] ); } ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="nb-r-section">
    <h2>Strategic direction<span class="nb-r-dot">.</span></h2>
    <p class="nb-r-sub">Where the team wants to take the business — and where they don't yet agree.</p>
    <?php
    $vectors = $aggregate['goal_vectors'];
    $vectors[] = array( 'key' => 'fix_invest', 'left' => 'Fix what’s urgent', 'right' => 'Invest long-term',
        'mean' => $aggregate['posture']['fix_invest']['mean'], 'spread' => $aggregate['posture']['fix_invest']['spread'],
        'split' => $aggregate['posture']['fix_invest']['spread'] >= NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD,
        'per_respondent' => $aggregate['posture']['fix_invest']['per_respondent'] );
    foreach ( $vectors as $v ) :
      $pct = ( $v['mean'] + 50 ) / 100 * 100; // -50..50 -> 0..100
      ?>
      <div class="nb-r-vec<?php echo $v['split'] ? ' is-split' : ''; ?>">
        <div class="nb-r-vec-row">
          <span class="nb-r-vec-cap"><?php echo esc_html( $v['left'] ); ?></span>
          <span class="nb-r-vec-track"><span class="nb-r-vec-mean" style="left:<?php echo esc_attr( $pct ); ?>%"></span></span>
          <span class="nb-r-vec-cap nb-r-vec-cap-r"><?php echo esc_html( $v['right'] ); ?></span>
        </div>
        <?php if ( $v['split'] ) : ?><p class="nb-r-split">⚑ Pulling different directions on this.</p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="nb-r-section">
    <h2>Timelines<span class="nb-r-dot">.</span></h2>
    <ul class="nb-r-list">
      <?php foreach ( $aggregate['posture']['timelines'] as $t ) : ?>
        <li><strong><?php echo esc_html( $t['name'] ); ?>:</strong> <?php echo esc_html( $t['timeline'] !== '' ? $t['timeline'] : '(blank)' ); ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="nb-r-section">
    <h2>In their words<span class="nb-r-dot">.</span></h2>
    <?php
    $qual_labels = array(
      'vision' => '3-year vision', 'open' => 'Anything else',
      'crm' => 'CRM today', 'lead_handling' => 'Lead handling today', 'reviews_system' => 'Reviews system',
      'call_tracking' => 'Call tracking', 'territories' => 'Territories', 'gbp_access' => 'Google Business Profile access',
    );
    foreach ( $qual_labels as $field => $label ) :
      if ( empty( $aggregate['qualitative'][ $field ] ) ) { continue; } ?>
      <h3 class="nb-r-qh"><?php echo esc_html( $label ); ?></h3>
      <ul class="nb-r-list">
        <?php foreach ( $aggregate['qualitative'][ $field ] as $q ) : ?>
          <li><strong><?php echo esc_html( $q['name'] ); ?>:</strong> <?php echo esc_html( $q['value'] !== '' ? $q['value'] : '(blank)' ); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endforeach; ?>
  </section>

  <?php endif; ?>

</main>
</body>
</html><?php
}
