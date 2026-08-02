import DetailTabContent from './DetailTabContent';

const renderEmptyTabSpecificContent = () => null;

const AIDiscoverabilityTabContent = (props) => <DetailTabContent { ...props } tabSpecificRenderer={ renderEmptyTabSpecificContent } />;

export default AIDiscoverabilityTabContent;
