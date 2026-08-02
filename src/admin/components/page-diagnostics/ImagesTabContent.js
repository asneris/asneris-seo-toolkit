import DetailTabContent from './DetailTabContent';

const renderEmptyTabSpecificContent = () => null;

const ImagesTabContent = (props) => <DetailTabContent { ...props } tabSpecificRenderer={ renderEmptyTabSpecificContent } />;

export default ImagesTabContent;
