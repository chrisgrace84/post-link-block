<?php
/**
 * Plugin Name:       Post Link Block
 * Description:       Output a Post link
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Christopher Grace
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       post-link-block
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Initialise Post Link Block
 */
add_action('init', function() {
    register_block_type(__DIR__ . '/build');
});

/**
 * Register WP-CLI Post Link Block search command
 */
add_action('cli_init', function() {
    if (class_exists('WP_CLI')) {
        $commandArgs = [
            'shortdesc' => 'Find all posts with an instance of the Post Link Block and return the Post ID/Title. Add optional date ranges.',
            'synopsis' => [
                [
                    'type' => 'assoc',
                    'name' => 'date-before',
                    'description' => 'Date to retrieve posts before. Accepts strtotime()-compatible string.',
                    'optional' => true,
                ],
                [
                    'type' => 'assoc',
                    'name' => 'date-after',
                    'description' => 'Date to retrieve posts after. Accepts strtotime()-compatible string.',
                    'optional' => true,
                    'default' => '30 days ago',
                ],
            ],
        ];

        WP_CLI::add_command('dmg-read-more search', 'searchPostLinksBlockInstances', $commandArgs);
    }
});

/**
 * Search for instances of the Post Link Block and log Posts to STDOUT
 *
 * @param $args
 * @param $assocArgs
 */
function searchPostLinksBlockInstances($args, $assocArgs) {
    global $wpdb;

    $postIdArray = [];

    $dateBefore = $assocArgs['date-before'] ?? null;
    $dateAfter  = $assocArgs['date-after'] ?? null;

    // Validate date arguments
    $testValidBefore = $dateBefore ? strtotime($dateBefore) : true;
    $testValidAfter  = $dateAfter ? strtotime($dateAfter) : true;

    if ($dateBefore && !$testValidBefore) {
        WP_CLI::error("Argument date-before is not strtotime()-compatible.");
    }

    if ($dateAfter && !$testValidAfter) {
        WP_CLI::error("Argument date-after is not strtotime()-compatible.");
    }

    try {
        // Build SQL query
        $where = [
            "post_status = 'publish'",
            "post_type = 'post'",
            "post_content LIKE %s"
        ];
        $params = ['%' . $wpdb->esc_like('wp:custom-block/post-link-block') . '%'];

        // Add date filters when present
        if ($dateAfter) {
            $where[] = "post_date >= %s";
            $params[] = date('Y-m-d H:i:s', strtotime($dateAfter));
        }

        if ($dateBefore) {
            $where[] = "post_date <= %s";
            $params[] = date('Y-m-d H:i:s', strtotime($dateBefore));
        }

        $whereSql = implode(' AND ', $where);

        /**
         * Fetch IDs
         */
        // For Optimised Performance
        $sqlQuery = "
            SELECT ID
            FROM $wpdb->posts
            WHERE $whereSql
        ";

        // For testing
        //$sqlQuery = "
        //    SELECT ID, post_title, post_date
        //    FROM $wpdb->posts
        //    WHERE $whereSql
        //    ORDER BY post_date DESC
        //";

        $query = $wpdb->prepare($sqlQuery, $params);
        $posts = $wpdb->get_results($query);

        if (empty($posts)) {
            WP_CLI::log('No posts found');
            return;
        }

        $progress = \WP_CLI\Utils\make_progress_bar('Searching Posts', count($posts));

        foreach ($posts as $post) {
            // For Optimised Performance
            $postIdArray[] = $post->ID;

            // Testing Only
            // $postIdArray[] = "Date: {$post->post_date} | ID: {$post->ID} | Title: {$post->post_title}";

            $progress->tick();
        }

        $progress->finish();
        $foundPosts = count($postIdArray);

        WP_CLI::line("----------");
        WP_CLI::log(implode("\n", $postIdArray));
        WP_CLI::line("----------");
        WP_CLI::success("{$foundPosts} posts found");

    } catch (\Exception $e) {
        // Error logs
        WP_CLI::warning("An error occurred: " . $e->getMessage());
        error_log("searchPostLinksBlockInstances error: " . $e->getMessage());
    }
}