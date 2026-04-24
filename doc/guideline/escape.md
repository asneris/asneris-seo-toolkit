When to Use Escaping - Simple Rules:

1. WHEN: Always on OUTPUT ⚠️
2. WHAT: Anything Dynamic
3. WHERE: By Context
Output Location	Function	Example
HTML tag content	esc_html()	<p><?php echo esc_html($text); ?></p>
HTML attribute	esc_attr()	<div class="<?php echo esc_attr($class); ?>">
URL (href, src)	esc_url()	<a href="<?php echo esc_url($link); ?>">
JavaScript string	esc_js()	var x = "<?php echo esc_js($val); ?>";
Textarea	esc_textarea()	<textarea><?php echo esc_textarea($text); ?></textarea>
Trusted HTML	wp_kses_post()	<?php echo wp_kses_post($html); ?>
4. WHEN NOT TO
5. REAL EXAMPLES from Your Code
6. MENTAL MODEL
Key principle:

Sanitize/Validate ONCE on input (clean the data)
Escape EVERY TIME on output (protect the context)
The difference:

Sanitize: Make data safe FOR storage
Escape: Make data safe FOR display context