<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function nb_discovery_render_page( $instance ) {
	// Standalone page embeds a per-request nonce — never let a page cache (e.g. Hummingbird) store it.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();

    $ver_css = newblood_asset_version( '/assets/css/discovery.css' );
    $ver_js  = newblood_asset_version( '/assets/js/discovery.js' );
    // Standalone page — wp_enqueue_* isn't available here, so cache-bust by filemtime() inline.
    $css_uri = get_template_directory_uri() . '/assets/css/discovery.css?v=' . $ver_css;
    $js_uri  = get_template_directory_uri() . '/assets/js/discovery.js?v=' . $ver_js;

    $cfg = wp_json_encode( array(
        'endpoint'  => esc_url_raw( rest_url( 'newblood/v1/discovery' ) ),
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'threshold' => 7,
        'instance'  => $instance['slug'],
    ) );

	if ( ! $cfg ) {
		$cfg = '{}';
	}

    $sc = $instance['section_copy'];
    ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( 'Discovery — ' . $instance['client_name'] ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( $css_uri ); ?>">
</head>
<body class="nb-d-body">
<main class="nb-d-shell">

  <header class="nb-d-welcome nb-d-section">
    <?php if ( $instance['logo'] ) : ?>
      <img class="nb-d-logo" src="<?php echo esc_url( $instance['logo'] ); ?>" alt="<?php echo esc_attr( $instance['client_name'] ); ?>">
    <?php endif; ?>
    <p class="nb-d-eyebrow">New Blood × <?php echo esc_html( $instance['client_name'] ); ?></p>
    <h1><?php echo esc_html( $instance['welcome']['title'] ); ?><span class="nb-d-dot">.</span></h1>
    <p class="nb-d-lede"><?php echo esc_html( $instance['welcome']['intro'] ); ?></p>
  </header>

  <form id="nb-discovery-form" data-instance="<?php echo esc_attr( $instance['slug'] ); ?>" novalidate>

		<div class="nb-d-hp" aria-hidden="true" style="position:absolute!important;left:-9999px!important;top:auto;width:1px;height:1px;overflow:hidden;">
			<label>Company website (leave blank)
				<input type="text" name="hp_company" tabindex="-1" autocomplete="off">
			</label>
		</div>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['priorities'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['priorities'][1] ); ?></p>
      <div class="nb-d-services">
        <?php foreach ( $instance['services'] as $s ) :
            $k = $s['key']; ?>
        <div class="nb-d-service" data-key="<?php echo esc_attr( $k ); ?>">
          <div class="nb-d-service-head">
            <span class="nb-d-service-label"><?php echo esc_html( $s['label'] ); ?></span>
            <span class="nb-d-service-hint"><?php echo esc_html( $s['hint'] ); ?></span>
          </div>
          <label class="nb-d-slider-row">
            <span class="nb-d-slider-cap">Not a priority</span>
            <input type="range" class="nb-d-importance" data-key="<?php echo esc_attr( $k ); ?>" min="0" max="10" value="0" step="1" aria-label="<?php echo esc_attr( $s['label'] . ' — importance' ); ?>">
            <span class="nb-d-slider-cap">Critical</span>
            <output class="nb-d-importance-out">0</output>
          </label>
          <div class="nb-d-handling" hidden>
            <label class="nb-d-slider-row">
              <span class="nb-d-slider-cap">Poorly</span>
              <input type="range" class="nb-d-handling-input" data-key="<?php echo esc_attr( $k ); ?>" min="0" max="10" value="0" step="1" aria-label="<?php echo esc_attr( $s['label'] . ' — handled today' ); ?>">
              <span class="nb-d-slider-cap">Very well</span>
              <output class="nb-d-handling-out">0</output>
            </label>
            <p class="nb-d-handling-q">How well is this handled today?</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['goals'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['goals'][1] ); ?></p>
      <label class="nb-d-field">
        <span>In 3 years, what does winning look like?</span>
        <textarea name="vision" rows="4"></textarea>
      </label>
      <?php foreach ( $instance['goal_vectors'] as $v ) : ?>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap"><?php echo esc_html( $v['left'] ); ?></span>
        <input type="range" class="nb-d-vector" data-key="<?php echo esc_attr( $v['key'] ); ?>" min="-50" max="50" value="0" step="1" aria-label="<?php echo esc_attr( $v['left'] . ' versus ' . $v['right'] ); ?>">
        <span class="nb-d-vector-cap"><?php echo esc_html( $v['right'] ); ?></span>
      </div>
      <?php endforeach; ?>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['systems'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['systems'][1] ); ?></p>
      <label class="nb-d-field"><span>Do you use a CRM today? If so, which one?</span><input type="text" name="crm"></label>
      <label class="nb-d-field"><span>When a web lead comes in today, what happens?</span><textarea name="lead_handling" rows="3"></textarea></label>
      <label class="nb-d-field"><span>Your reviews live in which system?</span><input type="text" name="reviews_system"></label>
      <label class="nb-d-field"><span>Any call-tracking / attribution in place? (e.g., Enspire)</span><input type="text" name="call_tracking"></label>
      <fieldset class="nb-d-field nb-d-radios">
        <legend>Can you grant manager access to your Google Business Profile?</legend>
        <label><input type="radio" name="gbp_access" value="yes"> Yes</label>
        <label><input type="radio" name="gbp_access" value="no"> No</label>
        <label><input type="radio" name="gbp_access" value="unsure" checked> Not sure</label>
      </fieldset>
      <label class="nb-d-field"><span>Which locations / territories should the plan cover?</span><textarea name="territories" rows="2"></textarea></label>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['direction'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['direction'][1] ); ?></p>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap">Fix what's urgent</span>
        <input type="range" class="nb-d-vector" data-key="fix_invest" min="-50" max="50" value="0" step="1" aria-label="Fix what is urgent versus invest for long-term growth">
        <span class="nb-d-vector-cap">Invest for long-term growth</span>
      </div>
      <label class="nb-d-field">
        <span>Ideal timeline to begin?</span>
        <select name="timeline">
          <option value="">Select…</option>
          <?php foreach ( $instance['timeline_options'] as $opt ) : ?>
          <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['open'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['open'][1] ); ?></p>
      <label class="nb-d-field"><span>Anything we haven't asked?</span><textarea name="open" rows="4"></textarea></label>
      <label class="nb-d-field"><span>Your name</span><input type="text" name="respondent_name"></label>
      <label class="nb-d-field"><span>Your email</span><input type="email" name="respondent_email"></label>
    </section>

    <div class="nb-d-actions">
      <button type="submit" id="nb-d-submit">Send to New Blood</button>
      <p class="nb-d-error" id="nb-d-error" hidden></p>
    </div>
  </form>

  <div id="nb-d-thankyou" class="nb-d-section" hidden>
    <h2>Thank you<span class="nb-d-dot">.</span></h2>
    <p class="nb-d-lede">We'll review your answers and prepare a plan built around your priorities. Jeremy will be in touch to walk through it with you.</p>
  </div>

</main>
<script>window.nbDiscovery = <?php echo $cfg; ?>;</script>
<script src="<?php echo esc_url( $js_uri ); ?>" defer></script>
</body>
</html><?php
}
