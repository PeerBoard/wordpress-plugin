<?php

namespace PEBO;

class PageTemplate
{

    protected $path_to_plugin;

    /**
     * The array of templates that this plugin tracks.
     */
    protected $templates;

    /**
     * Initializes the plugin by setting filters and administration functions.
     */
    public function __construct(array $templates, string $path_to_plugin)
    {

        $this->templates = array();


        // Add a filter to the attributes metabox to inject template into the cache.
        if (version_compare(floatval(get_bloginfo('version')), '4.7', '<')) {

            // 4.6 and older
            add_filter('page_attributes_dropdown_pages_args', [$this, 'register_project_templates']);
        } else {

            // Add a filter to the wp 4.7 version attributes metabox
            add_filter('theme_page_templates', [$this, 'add_new_template']);
        }

        // Add a filter to the save post to inject out template into the page cache
        add_filter('wp_insert_post_data', [$this, 'register_project_templates']);


        // Add a filter to the template include to determine if the page has our
        // template assigned and return it's path
        add_filter('template_include', [$this, 'show_custom_template']);


        // Templates array.
        $this->templates = $templates;

        // Path to plugin templates folder
        $this->path_to_templates = $path_to_plugin;
    }

    /**
     * Adds our template to the page dropdown for v4.7+
     *
     */
    public function add_new_template($posts_templates)
    {
        $posts_templates = array_merge($posts_templates, $this->templates);
        return $posts_templates;
    }

    /**
     * Adds our template to the pages cache in order to trick WordPress
     * into thinking the template file exists where it doens't really exist.
     */
    public function register_project_templates($atts)
    {

        // Create the key used for the themes cache
        $cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());

        // Retrieve the cache list.
        // If it doesn't exist, or it's empty prepare an array
        $templates = wp_get_theme()->get_page_templates();
        if (empty($templates)) {
            $templates = array();
        }

        // New cache, therefore remove the old one
        wp_cache_delete($cache_key, 'themes');

        // Now add our template to the list of templates by merging our templates
        // with the existing templates array from the cache.
        $templates = array_merge($templates, $this->templates);

        // Add the modified cache to allow WordPress to pick it up for listing
        // available templates
        wp_cache_add($cache_key, $templates, 'themes', 1800);

        return $atts;
    }

    /**
     * Checks if the template is assigned to the page
     */
    public function show_custom_template($template)
    {
        // Return the search template if we're searching (instead of the template for the first result)
        if (is_search()) {
            return $template;
        }

        // Get global post
        global $post;

        // Return template if post is empty
        if (!$post) {
            return $template;
        }

        // Return default template if we don't have a custom one defined
        if (!isset($this->templates[get_post_meta(
            $post->ID,
            '_wp_page_template',
            true
        )])) {
            return $template;
        }

        // Allows filtering of file path
        $filepath = $this->path_to_templates . 'templates/';

        $file =  $filepath . get_post_meta($post->ID, '_wp_page_template', true);

        // Just to be safe, we check if the file exist first
        if (file_exists($file)) {
            return $file;
        } else {
            $notice = sprintf('%s : %s', __('Template file not found', 'peerboard'), $file);
            peerboard_add_notice($notice, __FUNCTION__, 'error', func_get_args());
        }

        // Return template
        return $template;
    }
}
