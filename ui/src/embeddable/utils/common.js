import { SELECTED_COUNTRY } from "../../seeds-commons/commonConstants";
import { CURRENT_TAB } from "../reducers/StoreConstants";

export const getTextWidth = (text, font) => {
    // re-use canvas object for better performance
    const canvas = document.createElement("canvas");
    const context = canvas.getContext("2d");
    context.font = font;
    const metrics = context.measureText(text);
    return metrics.width;
}

export const lightenDarkenColor = (col, amt) => {

    let usePound = false;

    if (col[0] === "#") {
        col = col.slice(1);
        usePound = true;
    }

    const num = parseInt(col, 16);

    let r = (num >> 16) + amt;

    if (r > 255) r = 255;
    else if (r < 0) r = 0;

    let b = ((num >> 8) & 0x00FF) + amt;

    if (b > 255) b = 255;
    else if (b < 0) b = 0;

    let g = (num & 0x0000FF) + amt;

    if (g > 255) g = 255;
    else if (g < 0) g = 0;

    return (usePound ? "#" : "") + (g | (b << 8) | (r << 16)).toString(16);

}
export const normalizeField = (f) => {
    return f.replace(/\s+/g, '-').replace(/[\])}[{(,]/g, '').toLowerCase();
}
export const getSlugFromFilters = (filters, filtersData, valuesFilterStore, selectedFilterStore) => {
    const slug = [];
    if (filters && filtersData) {
        //TODO add object id (countryId) as parameter
        if (valuesFilterStore && filtersData.get(valuesFilterStore)
            && selectedFilterStore && filters.get(selectedFilterStore)) {
            if (filters.get(selectedFilterStore) === -1) {
                return filtersData.get(valuesFilterStore)
                    .filter(c => c.countryId !== filters.get(selectedFilterStore))
                    .map(c => normalizeField(c.country));
            } else {
                const filterSelected = filtersData.get(valuesFilterStore)
                    .find(fd => fd.countryId === filters.get(selectedFilterStore));
                if (filterSelected) {
                    //TODO add object value (country) as parameter
                    slug.push(normalizeField(filterSelected.country));
                }
            }
        }
    }
    return slug;
}

export const generateShareParams = (filters, chartType, selectedCrops, selectedYear) => {
    let selectedCountry;
    let selectedTab;
    if (filters) {
        if (filters.get(SELECTED_COUNTRY)) {
            selectedCountry = filters.get(SELECTED_COUNTRY);
        }

        if (filters.get(CURRENT_TAB)) {
            selectedTab = filters.get(CURRENT_TAB);
        }
    }

    let finalUrl = '';
    if (selectedTab) {
        finalUrl = finalUrl + `/tab=${selectedTab}`
    }

    if (selectedCrops && selectedCrops.length > 0) {
        finalUrl = finalUrl + `/crops=${selectedCrops.join(",")}`;
    }
    if (chartType) {
        finalUrl = finalUrl + `/chart=${chartType}`;
    }
    if (selectedYear && selectedYear.length > 0) {
        finalUrl = finalUrl + `/years=${Array.isArray(selectedYear) ? selectedYear.join(",") : selectedYear}`;
    }
    if (selectedCountry) {
        finalUrl = `#country=${selectedCountry}` + finalUrl;
    } else {
        if (finalUrl.length > 0) {
            finalUrl = "#" + finalUrl.substring(1)
        }
    }
    return finalUrl;
}
