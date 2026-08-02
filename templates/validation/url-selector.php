<?php
/**
 * URL Selector Component.
 *
 * @package Asneris_SEO_Toolkit
 * @var array $published_posts List of published posts/pages.
 * @var string $test_url Current test URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ASNERISSEO-card">
	<h2><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Test Any Page', 'asneris-seo-toolkit' ); ?></h2>

	<form method="post" action="" id="ASNERISSEO-validation-form">
		<?php wp_nonce_field( 'ASNERISSEO_validation' ); ?>

		<!-- Quick Actions -->
		<div class="ASNERISSEO-quick-actions" style="margin-bottom: 15px;">
			<button type="button" class="button ASNERISSEO-quick-test" data-url="<?php echo esc_url( home_url( '/' ) ); ?>">
				🏠 <?php esc_html_e( 'Test Homepage', 'asneris-seo-toolkit' ); ?>
			</button>
			<?php
			if ( ! empty( $published_posts ) ) :
				$asnerisseo_latest_post = null;
				$asnerisseo_latest_page = null;
				foreach ( $published_posts as $asnerisseo_published_post ) {
					if ( 'post' === $asnerisseo_published_post->post_type && ! $asnerisseo_latest_post ) {
						$asnerisseo_latest_post = $asnerisseo_published_post;
					}
					if ( 'page' === $asnerisseo_published_post->post_type && ! $asnerisseo_latest_page ) {
						$asnerisseo_latest_page = $asnerisseo_published_post;
					}
				}
				if ( $asnerisseo_latest_post ) :
					?>
			<button type="button" class="button ASNERISSEO-quick-test" data-url="<?php echo esc_url( get_permalink( $asnerisseo_latest_post->ID ) ); ?>">
				📝 <?php esc_html_e( 'Test Latest Post', 'asneris-seo-toolkit' ); ?>
			</button>
			<?php endif; if ( $asnerisseo_latest_page ) : ?>
			<button type="button" class="button ASNERISSEO-quick-test" data-url="<?php echo esc_url( get_permalink( $asnerisseo_latest_page->ID ) ); ?>">
				📄 <?php esc_html_e( 'Test Latest Page', 'asneris-seo-toolkit' ); ?>
			</button>
					<?php
			endif;
endif;
			?>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="ASNERISSEO_page_selector"><?php esc_html_e( 'Select Published Content', 'asneris-seo-toolkit' ); ?></label>
				</th>
				<td>
					<select id="ASNERISSEO_page_selector" class="large-text" style="margin-bottom: 10px;">
						<option value=""><?php esc_html_e( '-- Select a page to test --', 'asneris-seo-toolkit' ); ?></option>
						<optgroup label="<?php esc_html_e( 'Homepage', 'asneris-seo-toolkit' ); ?>">
							<option value="<?php echo esc_url( home_url( '/' ) ); ?>">🏠 <?php esc_html_e( 'Homepage', 'asneris-seo-toolkit' ); ?></option>
						</optgroup>

						<?php if ( ! empty( $published_posts ) ) : ?>
						<optgroup label="<?php esc_html_e( 'Recent Posts & Pages (100)', 'asneris-seo-toolkit' ); ?>">
							<?php
							foreach ( $published_posts as $asnerisseo_published_post ) :
								$asnerisseo_icon      = 'post' === $asnerisseo_published_post->post_type ? '📝' : '📄';
								$asnerisseo_permalink = get_permalink( $asnerisseo_published_post->ID );
								?>
							<option value="<?php echo esc_url( $asnerisseo_permalink ); ?>">
								<?php echo esc_html( $asnerisseo_icon ); ?> <?php echo esc_html( $asnerisseo_published_post->post_title ); ?>
								(<?php echo esc_html( $asnerisseo_published_post->post_type ); ?>)
							</option>
							<?php endforeach; ?>
						</optgroup>
						<?php endif; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="test_url"><?php esc_html_e( 'Or Enter Custom URL', 'asneris-seo-toolkit' ); ?></label>
				</th>
				<td>
					<input type="url" id="test_url" name="test_url" class="regular-text"
						value="<?php echo esc_attr( $test_url ); ?>"
						placeholder="<?php esc_html_e( 'https://yoursite.com/any-page/', 'asneris-seo-toolkit' ); ?>">
					<p class="description"><?php esc_html_e( 'Enter any URL from your site to validate SEO implementation', 'asneris-seo-toolkit' ); ?></p>
				</td>
			</tr>
		</table>

		<button type="submit" name="run_validation" class="button button-primary button-large">
			<span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
			<?php esc_html_e( 'Run Validation', 'asneris-seo-toolkit' ); ?>
		</button>
		<p class="description" style="margin-top: 8px; color: #646970;">
			<?php esc_html_e( 'This inspection does not modify the page.', 'asneris-seo-toolkit' ); ?>
		</p>
	</form>
</div>
