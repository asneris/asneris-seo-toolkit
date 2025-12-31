import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { PanelBody, TextControl, TextareaControl, ToggleControl, SelectControl, Button, ExternalLink, Notice } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const IndexNowSubmit = () => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [notice, setNotice] = useState(null);
  const postId = useSelect(select => select('core/editor').getCurrentPostId());
  const postStatus = useSelect(select => select('core/editor').getEditedPostAttribute('status'));
  
  const handleSubmit = () => {
    setIsSubmitting(true);
    setNotice(null);
    
    window.jQuery.ajax({
      url: window.ajaxurl,
      type: 'POST',
      data: {
        action: 'gscseo_manual_indexnow',
        nonce: window.gscseo_indexnow_nonce,
        post_id: postId
      },
      success: (response) => {
        setIsSubmitting(false);
        if (response.success) {
          setNotice({ type: 'success', message: response.data.message });
        } else {
          setNotice({ type: 'error', message: response.data.message });
        }
        // Clear notice after 5 seconds
        setTimeout(() => setNotice(null), 5000);
      },
      error: () => {
        setIsSubmitting(false);
        setNotice({ type: 'error', message: __('Request failed', 'bfseo') });
        setTimeout(() => setNotice(null), 5000);
      }
    });
  };
  
  if (postStatus !== 'publish') {
    return (
      <Notice status="warning" isDismissible={false}>
        {__('Post must be published to submit to IndexNow', 'bfseo')}
      </Notice>
    );
  }
  
  return (
    <>
      {notice && (
        <Notice status={notice.type} isDismissible={false} style={{ marginBottom: '12px' }}>
          {notice.message}
        </Notice>
      )}
      
      <p style={{ marginBottom: '12px', color: '#646970', fontSize: '13px' }}>
        {__('Manually notify search engines about this page update via IndexNow protocol.', 'bfseo')}
      </p>
      
      <Button
        variant="secondary"
        onClick={handleSubmit}
        disabled={isSubmitting}
        style={{ width: '100%' }}
      >
        {isSubmitting ? __('Submitting...', 'bfseo') : __('Submit to IndexNow', 'bfseo')}
      </Button>
      
      <p style={{ marginTop: '12px', color: '#646970', fontSize: '12px', fontStyle: 'italic' }}>
        {__('Note: IndexNow must be enabled in plugin settings.', 'bfseo')}
      </p>
    </>
  );
};

const MetaField = ({ label, metaKey, help, type = 'text', placeholder = '' }) => {
  const value = useSelect(
    select => select('core/editor').getEditedPostAttribute('meta')[metaKey] || '',
    [metaKey]
  );
  const { editPost } = useDispatch('core/editor');
  
  const Component = type === 'textarea' ? TextareaControl : TextControl;
  
  return (
    <Component
      label={label}
      value={value}
      onChange={(v) => editPost({ meta: { [metaKey]: v } })}
      help={help}
      placeholder={placeholder}
    />
  );
};

const RobotsControl = ({ metaKey, label, options }) => {
  const value = useSelect(
    select => select('core/editor').getEditedPostAttribute('meta')[metaKey] || options[0].value,
    [metaKey]
  );
  const { editPost } = useDispatch('core/editor');
  
  return (
    <SelectControl
      label={label}
      value={value}
      options={options}
      onChange={(v) => editPost({ meta: { [metaKey]: v } })}
    />
  );
};

const CharacterCount = ({ text, maxLength, optimal }) => {
  const length = text ? text.length : 0;
  let color = '#46b450'; // green
  
  if (length === 0) {
    color = '#646970'; // gray
  } else if (length < optimal.min || length > optimal.max) {
    color = '#dba617'; // warning
  } else if (length > maxLength) {
    color = '#d63638'; // error
  }
  
  return (
    <div style={{ 
      fontSize: '12px', 
      color: color, 
      marginTop: '-8px', 
      marginBottom: '12px',
      fontWeight: '500'
    }}>
      {length} / {maxLength} characters {optimal && `(optimal: ${optimal.min}-${optimal.max})`}
    </div>
  );
};

const SEOScore = () => {
  const meta = useSelect(select => select('core/editor').getEditedPostAttribute('meta'));
  const title = useSelect(select => select('core/editor').getEditedPostTitle());
  
  const [score, setScore] = useState(0);
  const [suggestions, setSuggestions] = useState([]);
  
  const calculateScore = () => {
    let points = 0;
    const tips = [];
    
    // Title check
    if (meta._gscseo_title && meta._gscseo_title.length >= 30 && meta._gscseo_title.length <= 60) {
      points += 20;
    } else {
      tips.push('Add an SEO title (30-60 characters)');
    }
    
    // Description check
    if (meta._gscseo_description && meta._gscseo_description.length >= 120 && meta._gscseo_description.length <= 160) {
      points += 20;
    } else {
      tips.push('Add a meta description (120-160 characters)');
    }
    
    // Canonical URL check
    if (meta._gscseo_canonical) {
      points += 15;
    }
    
    // OG Title check
    if (meta._gscseo_og_title) {
      points += 15;
    } else {
      tips.push('Add an Open Graph title for better social sharing');
    }
    
    // OG Description check
    if (meta._gscseo_og_description) {
      points += 15;
    }
    
    // OG Image check
    if (meta._gscseo_og_image) {
      points += 15;
    } else {
      tips.push('Add an Open Graph image');
    }
    
    setScore(points);
    setSuggestions(tips);
  };
  
  // Recalculate on meta changes
  useState(() => {
    calculateScore();
  }, [meta]);
  
  const getScoreColor = () => {
    if (score >= 80) return '#46b450';
    if (score >= 50) return '#dba617';
    return '#d63638';
  };
  
  return (
    <div style={{ 
      padding: '16px', 
      background: '#f6f7f7', 
      borderRadius: '4px',
      marginBottom: '16px'
    }}>
      <div style={{ 
        display: 'flex', 
        alignItems: 'center', 
        justifyContent: 'space-between',
        marginBottom: '8px'
      }}>
        <strong>SEO Score</strong>
        <span style={{ 
          fontSize: '24px', 
          fontWeight: 'bold',
          color: getScoreColor()
        }}>
          {score}%
        </span>
      </div>
      
      <div style={{ 
        height: '8px', 
        background: '#ddd', 
        borderRadius: '4px',
        overflow: 'hidden'
      }}>
        <div style={{ 
          height: '100%', 
          width: score + '%',
          background: getScoreColor(),
          transition: 'width 0.3s ease'
        }} />
      </div>
      
      {suggestions.length > 0 && (
        <div style={{ marginTop: '12px' }}>
          <strong style={{ fontSize: '12px', color: '#646970' }}>Suggestions:</strong>
          <ul style={{ 
            margin: '8px 0 0 0', 
            padding: '0 0 0 20px',
            fontSize: '12px',
            color: '#646970'
          }}>
            {suggestions.map((tip, index) => (
              <li key={index}>{tip}</li>
            ))}
          </ul>
        </div>
      )}
      
      <Button 
        isSecondary 
        isSmall
        onClick={calculateScore}
        style={{ marginTop: '12px', width: '100%' }}
      >
        Recalculate Score
      </Button>
    </div>
  );
};

registerPlugin('gscseo-sidebar', {
  render() {
    const schemaEnabled = useSelect(select =>
      select('core/editor').getEditedPostAttribute('meta')._gscseo_schema_enabled
    );
    const { editPost } = useDispatch('core/editor');
    
    return (
      <>
        <PluginSidebarMoreMenuItem target="gscseo-sidebar">
          {__('Clarity-First SEO', 'bfseo')}
        </PluginSidebarMoreMenuItem>
        
        <PluginSidebar
          name="gscseo-sidebar"
          title={__('Clarity-First SEO', 'bfseo')}
          icon="search"
        >
          <PanelBody 
            title={__('SEO Overview', 'bfseo')} 
            initialOpen={true}
          >
            <SEOScore />
            <ExternalLink href="/wp-admin/options-general.php?page=gscseo">
              {__('Open SEO Settings', 'bfseo')}
            </ExternalLink>
          </PanelBody>
          
          <PanelBody 
            title={__('Search Appearance', 'bfseo')} 
            initialOpen={true}
          >
            <MetaField 
              label={__('SEO Title', 'bfseo')}
              metaKey="_gscseo_title" 
              placeholder="Custom title for search engines"
              help="Leave empty to use the post title"
            />
            <CharacterCount 
              text={useSelect(select => 
                select('core/editor').getEditedPostAttribute('meta')._gscseo_title
              )}
              maxLength={60}
              optimal={{ min: 30, max: 60 }}
            />
            
            <MetaField 
              label={__('Meta Description', 'bfseo')}
              metaKey="_gscseo_description"
              type="textarea"
              placeholder="Brief description of your content"
              help="This appears in search results"
            />
            <CharacterCount 
              text={useSelect(select => 
                select('core/editor').getEditedPostAttribute('meta')._gscseo_description
              )}
              maxLength={160}
              optimal={{ min: 120, max: 160 }}
            />
            
            <MetaField 
              label={__('Canonical URL', 'bfseo')}
              metaKey="_gscseo_canonical"
              placeholder="https://example.com/canonical-url"
              help="Leave empty to use the current URL"
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Robots Meta', 'bfseo')} 
            initialOpen={false}
          >
            <RobotsControl 
              label={__('Index', 'bfseo')}
              metaKey="_gscseo_robots_index"
              options={[
                { label: 'Index (allow search engines)', value: 'index' },
                { label: 'No Index (hide from search)', value: 'noindex' }
              ]}
            />
            
            <RobotsControl 
              label={__('Follow', 'bfseo')}
              metaKey="_gscseo_robots_follow"
              options={[
                { label: 'Follow (allow link following)', value: 'follow' },
                { label: 'No Follow (prevent link following)', value: 'nofollow' }
              ]}
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Social Media (Open Graph)', 'bfseo')} 
            initialOpen={false}
          >
            <MetaField 
              label={__('Social Title', 'bfseo')}
              metaKey="_gscseo_og_title"
              placeholder="Title for social media"
              help="Leave empty to use SEO title"
            />
            
            <MetaField 
              label={__('Social Description', 'bfseo')}
              metaKey="_gscseo_og_description"
              type="textarea"
              placeholder="Description for social media"
              help="Leave empty to use meta description"
            />
            
            <MetaField 
              label={__('Social Image URL', 'bfseo')}
              metaKey="_gscseo_og_image"
              placeholder="https://example.com/image.jpg"
              help="Recommended: 1200x630px"
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Schema (Structured Data)', 'bfseo')} 
            initialOpen={false}
          >
            <ToggleControl
              label={__('Enable Article Schema', 'bfseo')}
              checked={schemaEnabled}
              onChange={(v) => editPost({ meta: { _gscseo_schema_enabled: v } })}
              help="Adds Article schema markup for better search results"
            />
          </PanelBody>

          <PanelBody 
            title={__('IndexNow', 'bfseo')} 
            initialOpen={false}
          >
            <IndexNowSubmit />
          </PanelBody>
        </PluginSidebar>
      </>
    );
  }
});
