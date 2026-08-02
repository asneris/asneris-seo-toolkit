import DetailTabContent from './DetailTabContent';

const renderEmptyTabSpecificContent = () => null;

const LinksTabContent = (props) => <DetailTabContent { ...props } tabSpecificRenderer={ renderEmptyTabSpecificContent } />;

export default LinksTabContent;
