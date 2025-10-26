import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
  PanelBody,
  TextControl,
  MenuItemsChoice,
  Button,
  Spinner,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import blockMeta from './block.json';

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const { className, ...blockProps } = useBlockProps();
  const { selectedPost } = attributes;

  const [posts, setPosts] = useState([]);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [loading, setLoading] = useState(true);
  const [searchValue, setSearchValue] = useState('');
  const perPage = 5;

  const fetchPosts = async (pageNumber, searchTerm) => {
    try {
      // Base URL
      const apiRoot = window.wpApiSettings?.root || '/wp-json/';
      let apiUrl = `${apiRoot}wp/v2/posts?status=publish&per_page=${perPage}&page=${pageNumber}`;

      if (searchTerm && /^\d+$/.test(searchTerm)) {
        apiUrl += `&include=${searchTerm}`;
      } else if (searchValue) {
        apiUrl += `&search=${encodeURIComponent(searchTerm)}`;
      }

      const response = await fetch(apiUrl);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const responsePosts = await response.json();
      setPosts(responsePosts);

      const totalPages = response.headers.get('X-WP-TotalPages');
      setTotalPages(Number(totalPages) || 1);
    } catch (error) {
      console.error('Error fetching posts:', error);
      setPosts([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPosts(page, searchValue);
  }, [page]);

  // Add debounce for search field to avoid unnecessary api calls
  // Wait 400ms after user stops typing
  useEffect(() => {
    let debounceTimer;

    debounceTimer = setTimeout(() => {
      setPage(1);
      fetchPosts(page, searchValue);
    }, 400);

    return () => clearTimeout(debounceTimer);
  }, [searchValue]);

  const options = posts.map((post) => ({
    label: post.title.rendered,
    value: post.id.toString(),
  }));

  return (
    <>
      {selectedPost && (
        <p className={`${className} dmg-read-more`} {...blockProps}>
          <a href={selectedPost.link}>
            {__(`Read More: `, blockMeta.textdomain)}
            {selectedPost.title.rendered}
          </a>
        </p>
      )}

      <InspectorControls>
        <PanelBody
          title={__(`Post Links ${blockMeta.textdomain}`, blockMeta.textdomain)}
          className="post-links-block-panel"
        >
          <TextControl
            label={__('Search Post ID or Title', blockMeta.textdomain)}
            value={searchValue}
            onChange={(searchValue) => setSearchValue(searchValue)}
            placeholder="Type here..."
          />
          {loading ? (
            <Spinner />
          ) : (
            <>
              {posts.length > 0 ? (
                <>
                  <MenuItemsChoice
                    choices={options}
                    value={selectedPost?.id?.toString() || ''}
                    onSelect={(id) => {
                      const post = posts.find((p) => p.id === parseInt(id, 10));
                      setAttributes({ selectedPost: post || null });
                    }}
                  />
                  {totalPages > 1 && (
                    <div className="post-links-block-panel__pagination">
                      <Button
                        disabled={page <= 1}
                        onClick={() => setPage(page - 1)}
                      >
                        {__('Previous', blockMeta.textdomain)}
                      </Button>
                      <span>
                        {`${page} ${__('of', blockMeta.textdomain)} ${totalPages}`}
                      </span>
                      <Button
                        disabled={page >= totalPages}
                        onClick={() => setPage(page + 1)}
                      >
                        {__('Next', blockMeta.textdomain)}
                      </Button>
                    </div>
                  )}
                </>
              ) : (
                <p>{__('No posts founds', blockMeta.textdomain)}</p>
              )}
            </>
          )}
        </PanelBody>
      </InspectorControls>
    </>
  );
}
