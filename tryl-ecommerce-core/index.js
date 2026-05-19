import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

// Register the TRYL Hero Block
registerBlockType('tryl/hero-block', {
    apiVersion: 2,
    title: 'TRYL Hero Section',
    icon: 'cover-image',
    category: 'design',
    description: 'The premium Nike-inspired split-text hero section.',
    
    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div { ...blockProps } style={{ border: '2px dashed #31d190', padding: '10px' }}>
                <div style={{ background: '#0d1b0f', color: '#fff', padding: '4px 8px', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.1em', display: 'inline-block', marginBottom: '8px' }}>Live Preview: TRYL Hero</div>
                {/* This tells WordPress to securely fetch our existing PHP function and render it live! */}
                <ServerSideRender block="tryl/hero-block" attributes={ props.attributes } />
            </div>
        );
    },
    save: function () { return null; } // Must return null for PHP Server-Side blocks
});

// Register the TRYL Prayer Form Block
registerBlockType('tryl/prayer-form-block', {
    apiVersion: 2,
    title: 'TRYL Prayer Form',
    icon: 'feedback',
    category: 'widgets',
    description: 'The secure prayer request submission form.',
    
    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div { ...blockProps } style={{ border: '2px dashed #0d1b0f', padding: '10px' }}>
                <div style={{ background: '#0d1b0f', color: '#fff', padding: '4px 8px', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.1em', display: 'inline-block', marginBottom: '8px' }}>Live Preview: TRYL Prayer Form</div>
                {/* ServerSideRender fetches the actual PHP shortcode output */}
                <ServerSideRender block="tryl/prayer-form-block" attributes={ props.attributes } />
            </div>
        );
    },
    save: function () { return null; } // Must return null for PHP Server-Side blocks
});

// Register the TRYL Prayer Wall Block
registerBlockType('tryl/prayer-wall-block', {
    apiVersion: 2,
    title: 'TRYL Prayer Wall',
    icon: 'heart',
    category: 'widgets',
    description: 'The public masonry grid of approved prayer requests.',
    
    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div { ...blockProps } style={{ border: '2px dashed #0d1b0f', padding: '10px' }}>
                <div style={{ background: '#0d1b0f', color: '#fff', padding: '4px 8px', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.1em', display: 'inline-block', marginBottom: '8px' }}>Live Preview: TRYL Prayer Wall</div>
                {/* ServerSideRender fetches the actual PHP shortcode output */}
                <ServerSideRender block="tryl/prayer-wall-block" attributes={ props.attributes } />
            </div>
        );
    },
    save: function () { return null; } // Must return null for PHP Server-Side blocks
});

// Register the TRYL 3D Shop Grid Block
registerBlockType('tryl/shop-grid-block', {
    apiVersion: 2,
    title: 'TRYL 3D Shop Grid',
    icon: 'grid-view',
    category: 'widgets',
    description: 'The premium 4-column product grid with GSAP size selectors and category filters.',
    
    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div { ...blockProps } style={{ border: '2px dashed #0d1b0f', padding: '10px' }}>
                <div style={{ background: '#0d1b0f', color: '#fff', padding: '4px 8px', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.1em', display: 'inline-block', marginBottom: '8px' }}>Live Preview: TRYL 3D Shop Grid</div>
                {/* ServerSideRender fetches the actual PHP shortcode output */}
                <ServerSideRender block="tryl/shop-grid-block" attributes={ props.attributes } />
            </div>
        );
    },
    save: function () { return null; } // Must return null for PHP Server-Side blocks
});

// Register the TRYL Order Tracker Block
registerBlockType('tryl/order-tracker-block', {
    apiVersion: 2,
    title: 'TRYL Order Tracker',
    icon: 'location',
    category: 'widgets',
    description: 'Public order tracking form with visual progress bar.',

    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps} style={{ border: '2px dashed #0d1b0f', padding: '10px' }}>
                <div style={{ background: '#0d1b0f', color: '#fff', padding: '4px 8px', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.1em', display: 'inline-block', marginBottom: '8px' }}>Live Preview: Order Tracker</div>
                <ServerSideRender block="tryl/order-tracker-block" attributes={props.attributes} />
            </div>
        );
    },
    save: function () { return null; } // Must return null for PHP Server-Side blocks
});

// Register the TRYL Complete The Look Block
registerBlockType('tryl/complete-look-block', {
    apiVersion: 2,
    title: 'TRYL Complete The Look',
    icon: 'cart',
    category: 'widgets',
    description: 'Displays dynamic cross-sell products on single product pages.',
    
    edit: function (props) {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps} style={{ border: '2px dashed #0d1b0f', padding: '10px' }}>
                <div style={{ background: '#0d1b0f', color: '#fff', padding: '4px 8px', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.1em', display: 'inline-block', margin: '0 0 8px 0' }}>Live Preview: Complete The Look</div>
                <ServerSideRender block="tryl/complete-look-block" attributes={props.attributes} />
            </div>
        );
    },
    save: function () { return null; }
});