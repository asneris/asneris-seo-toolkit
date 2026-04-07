import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { PanelBody, TextControl, TextareaControl, ToggleControl, SelectControl, Button, ExternalLink, Notice } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

// Template resolver helper
const getTemplateValues = (postTitle, siteName, metaValue, separator = '|') => {
  if (metaValue) return null; // Manual override exists
  
  // Simulate template resolution (matches class-render.php logic)
  const resolvedTitle = `${postTitle || 'Untitled'} ${separator} ${siteName || 'Site'}`;
  return resolvedTitle;
};

const IndexNowSubmit = () => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [notice, setNotice] = useState(null);
  const postId = useSelect(select => select('core/editor').getCurrentPostId());
  const postStatus = useSelect(select => select('core/editor').getEditedPostAttribute('status'));
  
  const handleSubmit = () => {
    setIsSubmitting(true);
    setNotice(null);
    
    const ajaxurl = window.asnerisseoData?.ajaxurl || window.ajaxurl || '/wp-admin/admin-ajax.php';
    const nonce = window.asnerisseoData?.indexnowNonce || window.ASNERISSEO_indexnow_nonce;
    
    fetch(ajaxurl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'ASNERISSEO_manual_indexnow',
        nonce: nonce,
        post_id: postId
      })
    })
    .then(response => response.json())
    .then(data => {
      setIsSubmitting(false);
      if (data.success) {
        setNotice({ type: 'success', message: data.data.message });
      } else {
        setNotice({ type: 'error', message: data.data.message || __('Failed to submit', 'asneris-seo-toolkit') });
      }
      setTimeout(() => setNotice(null), 5000);
    })
    .catch(error => {
      setIsSubmitting(false);
      setNotice({ type: 'error', message: __('Request failed', 'asneris-seo-toolkit') });
      setTimeout(() => setNotice(null), 5000);
    });
  };
  
  if (postStatus !== 'publish') {
    return (
      <Notice status="warning" isDismissible={false}>
        {__('Post must be published to submit to IndexNow', 'asneris-seo-toolkit')}
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
        {__('Manually notify search engines about this page update via IndexNow protocol.', 'asneris-seo-toolkit')}
      </p>
      
      <Button
        variant="secondary"
        onClick={handleSubmit}
        disabled={isSubmitting}
        style={{ width: '100%' }}
      >
        {isSubmitting ? __('Submitting...', 'asneris-seo-toolkit') : __('Submit to IndexNow', 'asneris-seo-toolkit')}
      </Button>
      
      <p style={{ marginTop: '12px', color: '#646970', fontSize: '12px', fontStyle: 'italic' }}>
        {__('Note: IndexNow must be enabled in plugin settings.', 'asneris-seo-toolkit')}
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
  
  // Get template preview
  const postTitle = useSelect(select => select('core/editor').getEditedPostAttribute('title'));
  const siteName = window.asnerisseoData?.siteName || 'Site';
  
  let templatePreview = null;
  if (!value && (metaKey === '_ASNERISSEO_title' || metaKey === '_ASNERISSEO_description')) {
    if (metaKey === '_ASNERISSEO_title') {
      templatePreview = getTemplateValues(postTitle, siteName, value);
    } else if (metaKey === '_ASNERISSEO_description') {
      templatePreview = 'Auto: Generated from post content excerpt';
    }
  }
  
  const Component = type === 'textarea' ? TextareaControl : TextControl;
  
  return (
    <>
      <Component
        label={label}
        value={value}
        onChange={(v) => editPost({ meta: { [metaKey]: v } })}
        help={help}
        placeholder={placeholder}
      />
      {templatePreview && (
        <div style={{ 
          fontSize: '12px', 
          color: '#646970', 
          marginTop: '-12px', 
          marginBottom: '12px',
          padding: '8px 10px',
          background: '#fff3cd',
          border: '1px solid #ffc107',
          borderRadius: '4px',
          fontStyle: 'italic'
        }}>
          <strong style={{ color: '#d63638', fontWeight: '600' }}>Auto:</strong> {templatePreview}
        </div>
      )}
    </>
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
  const title = useSelect(select => select('core/editor').getEditedPostAttribute('title'));
  const postTitle = useSelect(select => select('core/editor').getEditedPostAttribute('title'));
  const siteName = window.asnerisseoData?.siteName || 'Site';
  
  const [score, setScore] = useState(0);
  const [suggestions, setSuggestions] = useState([]);
  
  const calculateScore = () => {
    let points = 0;
    const tips = [];
    
    // Title check - factor in template-generated value if no manual override
    const effectiveTitle = meta._ASNERISSEO_title || getTemplateValues(postTitle, siteName, meta._ASNERISSEO_title);
    if (effectiveTitle && effectiveTitle.length >= 30 && effectiveTitle.length <= 60) {
      points += 20;
    } else if (!effectiveTitle || effectiveTitle.length < 30) {
      tips.push('SEO title should be 30-60 characters');
    } else if (effectiveTitle.length > 60) {
      tips.push('SEO title is too long (max 60 characters)');
    }
    
    // Description check - factor in auto-generated if no manual override
    const effectiveDesc = meta._ASNERISSEO_description;
    if (effectiveDesc && effectiveDesc.length >= 120 && effectiveDesc.length <= 160) {
      points += 20;
    } else if (!effectiveDesc) {
      // Even without manual override, description is auto-generated
      points += 10; // Partial credit for auto-generated
      tips.push('Add a custom meta description for better results (120-160 characters)');
    } else if (effectiveDesc.length < 120) {
      tips.push('Meta description is too short (min 120 characters)');
    } else if (effectiveDesc.length > 160) {
      tips.push('Meta description is too long (max 160 characters)');
    }
    
    // Canonical URL check
    if (meta._ASNERISSEO_canonical) {
      points += 15;
    }
    
    // OG Title check - uses SEO title as fallback
    if (meta._ASNERISSEO_og_title || meta._ASNERISSEO_title) {
      points += 15;
    } else {
      tips.push('Add an Open Graph title for better social sharing');
    }
    
    // OG Description check - uses meta description as fallback
    if (meta._ASNERISSEO_og_description || meta._ASNERISSEO_description) {
      points += 15;
    }
    
    // OG Image check
    if (meta._ASNERISSEO_og_image) {
      points += 15;
    } else {
      tips.push('Add an Open Graph image for social media previews');
    }
    
    setScore(points);
    setSuggestions(tips);
  };
  
  // Recalculate on meta changes
  useEffect(() => {
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

registerPlugin('asneris-seo-sidebar', {
  render() {
    const schemaEnabled = useSelect(select =>
      select('core/editor').getEditedPostAttribute('meta')._ASNERISSEO_schema_enabled
    );
    const { editPost } = useDispatch('core/editor');
    const { openGeneralSidebar } = useDispatch('core/edit-post');
    
    // Auto-open sidebar if URL parameter is present
    useEffect(() => {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('asneris-seo-open') === '1') {
        // Multiple attempts to ensure sidebar opens
        const attempts = [500, 1000, 1500, 2000];
        attempts.forEach(delay => {
          setTimeout(() => {
            openGeneralSidebar('asneris-seo-sidebar/asneris-seo-sidebar');
          }, delay);
        });
      }
    }, [openGeneralSidebar]);
    
    return (
      <>
        <PluginSidebarMoreMenuItem target="asneris-seo-sidebar">
          {__('Asneris SEO Toolkit', 'asneris-seo-toolkit')}
        </PluginSidebarMoreMenuItem>
        
        <PluginSidebar
          name="asneris-seo-sidebar"
          title={__('Asneris SEO Toolkit', 'asneris-seo-toolkit')}
          icon="search"
        >
          <PanelBody 
            title={__('SEO Overview', 'asneris-seo-toolkit')} 
            initialOpen={true}
          >
            <SEOScore />
            <ExternalLink href="/wp-admin/admin.php?page=asneris-seo">
              {__('Open SEO Settings', 'asneris-seo-toolkit')}
            </ExternalLink>
          </PanelBody>
          
          <PanelBody 
            title={__('Search Appearance', 'asneris-seo-toolkit')} 
            initialOpen={true}
          >
            <MetaField 
              label={__('SEO Title', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_title" 
              placeholder="Custom title for search engines"
              help="Leave empty to use the post title"
            />
            <CharacterCount 
              text={useSelect(select => 
                select('core/editor').getEditedPostAttribute('meta')._ASNERISSEO_title
              )}
              maxLength={60}
              optimal={{ min: 30, max: 60 }}
            />
            
            <MetaField 
              label={__('Meta Description', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_description"
              type="textarea"
              placeholder="Brief description of your content"
              help="This appears in search results"
            />
            <CharacterCount 
              text={useSelect(select => 
                select('core/editor').getEditedPostAttribute('meta')._ASNERISSEO_description
              )}
              maxLength={160}
              optimal={{ min: 120, max: 160 }}
            />
            
            <MetaField 
              label={__('Canonical URL', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_canonical"
              placeholder="https://example.com/canonical-url"
              help="Leave empty to use the current URL"
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Robots Meta', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <RobotsControl 
              label={__('Index', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_robots_index"
              options={[
                { label: 'Index (allow search engines)', value: 'index' },
                { label: 'No Index (hide from search)', value: 'noindex' }
              ]}
            />
            
            <RobotsControl 
              label={__('Follow', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_robots_follow"
              options={[
                { label: 'Follow (allow link following)', value: 'follow' },
                { label: 'No Follow (prevent link following)', value: 'nofollow' }
              ]}
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Social Media (Open Graph)', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <MetaField 
              label={__('Social Title', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_og_title"
              placeholder="Title for social media"
              help="Leave empty to use SEO title"
            />
            
            <MetaField 
              label={__('Social Description', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_og_description"
              type="textarea"
              placeholder="Description for social media"
              help="Leave empty to use meta description"
            />
            
            <MetaField 
              label={__('Social Image URL', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_og_image"
              placeholder="https://example.com/image.jpg"
              help="Recommended: 1200x630px"
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Schema (Structured Data)', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <ToggleControl
              label={__('Enable Schema', 'asneris-seo-toolkit')}
              checked={schemaEnabled}
              onChange={(v) => editPost({ meta: { _ASNERISSEO_schema_enabled: v } })}
              help="Adds structured data markup for better search results"
            />
            
            {schemaEnabled && (
              <RobotsControl
                label={__('Schema Type', 'asneris-seo-toolkit')}
                metaKey="_ASNERISSEO_schema_type"
                options={[
                  { label: 'Auto-detect (recommended)', value: '' },
                  { label: 'Article / Blog Post', value: 'Article' },
                  { label: 'News Article', value: 'NewsArticle' },
                  { label: 'Blog Posting', value: 'BlogPosting' },
                  { label: 'Web Page', value: 'WebPage' },
                  { label: 'Product', value: 'Product' },
                  { label: 'Event', value: 'Event' },
                  { label: 'Course', value: 'Course' },
                  { label: 'Recipe', value: 'Recipe' },
                  { label: 'Video', value: 'VideoObject' },
                  { label: 'FAQ Page', value: 'FAQPage' },
                  { label: 'How-To', value: 'HowTo' },
                  { label: 'Job Posting', value: 'JobPosting' },
                  { label: 'Service', value: 'Service' },
                ]}
              />
            )}
          </PanelBody>

          <PanelBody 
            title={__('IndexNow', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <IndexNowSubmit />
          </PanelBody>
        </PluginSidebar>
      </>
    );
  }
});
