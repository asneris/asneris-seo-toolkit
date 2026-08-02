import DetailTabContent from './DetailTabContent';

const renderEmptyTabSpecificContent = () => null;

const ContentTabContent = (props) => <DetailTabContent { ...props } tabSpecificRenderer={ renderEmptyTabSpecificContent } />;

export default ContentTabContent;
