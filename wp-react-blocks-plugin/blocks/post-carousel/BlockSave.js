import {FIELD_ORIENTATION_VERTICAL} from "./index";

const SaveComponent = (props) => {
    const {
        setAttributes,
        attributes: {
            count,
            height,
            type,
            taxonomy,
            categories,
            itemsPerPage,
            valuesFilterStore,
            selectedFilterStore,
            scheduledFilter,
            scheduledFilterStore,
            connectFilter,
            fieldOrientation,
            navigatorStyle,
            showLinksInModal,
            sortedByCountryAndYearCategories,
            preloadDocumentsAndCrops,
            isNewImplementation,
            defaultCategory
        },
    } = props;

    const divClass = {}
    const divStyles = {}
    return (<div className={divClass} style={divStyles}>
            <div data-items={count} data-type={type}
                 data-filter-default-category={defaultCategory}
                 data-items-per-page={itemsPerPage}
                 data-taxonomy={taxonomy}
                 data-values-filter-store={valuesFilterStore}
                 data-selected-filter-store={selectedFilterStore}
                 data-connect-filter={connectFilter}
                 data-orientation={fieldOrientation}
                 data-navigator-style={navigatorStyle}
                 data-height={height}
                 data-categories={categories.toString()}
                 data-show-links-in-modal={showLinksInModal}
                 data-show-sorted-by-country-and-year-categories={sortedByCountryAndYearCategories}
                 data-preload-document-and-crops={preloadDocumentsAndCrops}
                 data-scheduled-filter={scheduledFilter}
                 data-scheduled-filter-store={scheduledFilterStore}
                 className={"wp-react-lib-component"}
                 data-new-implementation={isNewImplementation}
                 data-component={"postsCarousel"}>

            </div>
        </div>


    );
}


export default SaveComponent
