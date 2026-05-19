import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

// These blocks are Server-Side Rendered (SSR) by your PHP callbacks.
// This file simply registers their UI presence in the Gutenberg inserter menu.

registerBlockType('tryl/hero-block', {
    title: 'TRYL Premium Hero',
    icon: 'cover-image',
    category: 'design',
    edit: () => <div {...useBlockProps()} style={{ padding: '20px', background: '#f5f8f5', border: '1px solid #d4e0d4', textAlign: 'center' }}><strong>TRYL Premium Hero</strong><br />(Live preview renders on frontend)</div>,
    save: () => null, // null tells WP to rely entirely on the PHP render_callback
});

registerBlockType('tryl/shop-grid-block', {
    title: 'TRYL 3D Shop Grid',
    icon: 'grid-view',
    category: 'widgets',
    edit: () => <div {...useBlockProps()} style={{ padding: '20px', background: '#f5f8f5', border: '1px solid #d4e0d4', textAlign: 'center' }}><strong>TRYL 3D Shop Grid</strong><br />(Live preview renders on frontend)</div>,
    save: () => null,
});

registerBlockType('tryl/prayer-form-block', {
    title: 'TRYL Prayer Form',
    icon: 'feedback',
    category: 'widgets',
    edit: () => <div {...useBlockProps()} style={{ padding: '20px', background: '#f5f8f5', border: '1px solid #d4e0d4', textAlign: 'center' }}><strong>TRYL Prayer Form</strong><br />(Live preview renders on frontend)</div>,
    save: () => null,
});

registerBlockType('tryl/prayer-wall-block', {
    title: 'TRYL Prayer Wall',
    icon: 'format-gallery',
    category: 'widgets',
    edit: () => <div {...useBlockProps()} style={{ padding: '20px', background: '#f5f8f5', border: '1px solid #d4e0d4', textAlign: 'center' }}><strong>TRYL Prayer Wall</strong><br />(Live preview renders on frontend)</div>,
    save: () => null,
});

registerBlockType('tryl/order-tracker-block', {
    title: 'TRYL Order Tracker (Beta)',
    icon: 'location-alt',
    category: 'widgets',
    edit: () => <div {...useBlockProps()} style={{ padding: '20px', background: '#f5f8f5', border: '1px solid #d4e0d4', textAlign: 'center' }}><strong>TRYL Order Tracker</strong><br />(Live preview renders on frontend)</div>,
    save: () => null,
});