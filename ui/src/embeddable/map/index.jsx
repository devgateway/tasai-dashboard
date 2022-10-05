import React, {useEffect, useRef, useState} from "react";
import {Button, Container, Grid, GridRow, Icon, Segment} from "semantic-ui-react";
import {connect} from "react-redux";
import {
    DATA,
    WP_CATEGORIES,
    COUNTRIES_FILTER,
    SOURCE_CATEGORIES,
    SHARE_CHART,
    SHARE_CROPS, DEFAULT_COUNTRY_ID, ADEQUACY_ACTIVE_BREEDERS, MAP_INDICATOR_DATA, AVAILABILITY_BASIC_SEED,
} from "../reducers/StoreConstants";
import {MapComponent} from './components/Map';
import {getCountries, getData, getMapIndicator, getWpCategories, setFilter} from "../reducers/data";
import {A1_ADEQUACY_ACTIVE_BREEDERS, A4_AVAILABILITY_FOUNDATION_SEED} from "./Constants";
import IndicatorFilter from "./components/IndicatorFilter";
import {injectIntl} from "react-intl";
import CropFilter from "../chart/common/filters/crops";
import Header from "../chart/common/header";
import './map.scss';
import HHILegend from "../chart/MarketConcentrationHHI/HHILegend";
import Export from "../chart/common/export";
import Source from "../chart/common/source";
import {cleanupParam} from "../chart/Countryinfo";

const Map = (props) => {
    const {filters} = props
    let indicators = [];
    let processedData = null;
    let initialSelectedCrops = [];
    let crops = null;
    const {
        parent,
        editing = false,
        unique,
        childContent,
        setDefaultFilter,
        onLoadCategories,
        onLoadIndicatorData,
        categoriesWP,
        countries,
        onLoadCountries,
        locale,
        intl,
        mapData,
        "data-app": app,
        "data-download": download,
        "data-height": height = 500,
        "data-map-type": type,
        "data-source-text_en": sourceText_en,
        "data-source-text_fr": sourceText_fr,
        "data-title": title = "",
        "data-sub-title": subTitle = "",
        "data-methodology": methodology,
        "data-map-data-source": mapDataSource,
        'data-params': params = '{}',
    } = props;

    useEffect(() => {
        setDefaultFilter(DEFAULT_COUNTRY_ID, 23);
        if (filters && filters.get(SHARE_CHART) && type === filters.get(SHARE_CHART)) {
            wrapper.current.scrollIntoView({block: 'end', behavior: 'smooth'});
        }
    }, []);

    useEffect(() => {
        onLoadCountries("latestCountryStudies");
    }, [onLoadCountries]);

    useEffect(() => {
        onLoadCategories()
    }, [onLoadCategories]);

    const [selectedIndicator, setSelectedIndicator] = useState(null);
    const [initialCrops, setInitialCrops] = useState(null);
    const [currentData, setCurrentData] = useState(null);
    const [selectedCrops, setSelectedCrops] = useState(null);

    useEffect(() => {
        onLoadIndicatorData(selectedIndicator.id);
    }, [selectedIndicator]);

    const currentLanguage = locale || 'en';
    let sourceText;
    if (currentLanguage === 'en') {
        if (cleanupParam(sourceText_en)) {
            sourceText = sourceText_en;
        } else {
            sourceText = cleanupParam(sourceText_fr) || '';
        }
    }

    const processCommonData = ()  => {
        processedData = [];
        if (mapData.values) {
            Object.keys(mapData.values).forEach(k => {
                if (selectedCrops) {
                    const item = Object.assign({}, mapData.values[k]);
                    item.id = k.toUpperCase()
                    item.value = item[selectedCrops];
                    item.country = countries.find(c => c.isoCode === item.id).country;
                    item.crop = selectedCrops;
                    if (item.value && item.value !== 'MD' && item.value !== 'NA') {
                        processedData.push(item);
                    } else {
                        console.warn('ignored not number.')
                    }
                }
            });
        }
    }

    if (mapData && mapData !== currentData) {
        setCurrentData(mapData);
        crops = mapData.dimensions.crop ? mapData.dimensions.crop.values : {};
        setInitialCrops(crops);
        initialSelectedCrops = null;
        setSelectedCrops(crops[0]);
    }

    const exportPng = (ref, type) => {
    }

    if (indicators.length === 0) {
        switch (type) {
            case "indicators_A":
                indicators = [
                    {value: A1_ADEQUACY_ACTIVE_BREEDERS, id: ADEQUACY_ACTIVE_BREEDERS},
                    {value: A4_AVAILABILITY_FOUNDATION_SEED, id: AVAILABILITY_BASIC_SEED}
                ];
                if (!selectedIndicator) {
                    setSelectedIndicator(indicators[0]);
                }
                break;
            case "indicators_B":

                break;

            case "indicators_C":

                break;

            case "indicators_D":

                break;

            case "indicators_E":

                break;
        }
    }

    const mapProps = {
        title: title,
        subTitle: subTitle,
        editing: editing,
        methodology: methodology,
        download: download,
        exportPng: exportPng,
    }

    const generateSourcesText = () => {
    }
    
    const handleCropFilterChange = (selected) => {
        const currentlySelected = [];
        for (let i = 0; i < selected.length; i++) {
            if (selected[i] === 1) {
                currentlySelected.push(initialCrops[i]);
            }
        }
        setSelectedCrops(currentlySelected);
    }
    
    const handleIndicatorChange = (selected) => {
        setSelectedIndicator(selected);
    }
        
    if (countries && mapData && !mapData.LOADING) {
        // TODO: prevent calling this method more times than needed.
        processCommonData();
    }

    const map_height = 750;
    const fixedHeightStyle = {};

    let dynamicSources = generateSourcesText();
    const mapComponent = {type, ...mapProps}
    const wrapper = useRef(null);
    
    // Needed for <CropFilter/>
    if (initialCrops) {
        initialSelectedCrops = [];
        initialCrops.forEach((c, i) => {
            initialSelectedCrops.push(i === 0 ? 1 : 0)
        });
    }
    
    const mapColors = colors.map(c => c.color);
    return (<div ref={wrapper}>
            <Container className={"map container"} fluid={true} style={{height: '850px', width: '100%'}}>
                <Grid className={`map-grid`}>
                    <Grid.Row className="header-section">
                        <Grid.Column width={12}>
                            <Header title={`${title}`} subtitle={subTitle} />
                        </Grid.Column>
                        <Grid.Column width={4}>
                            <Export methodology={methodology} exportPng={exportPng} download={download} containerRef={wrapper}
                                    type={'bar'} chartType={type} selectedCrops={selectedCrops} />
                        </Grid.Column>
                    </Grid.Row>
                    <Grid.Row className={`filters-section`}>
                        <Grid.Column width={6}>
                            <IndicatorFilter intl={intl} data={indicators} initialSelectedIndicator={selectedIndicator} onChange={handleIndicatorChange} />
                        </Grid.Column>
                        <Grid.Column width={4}>
                            {initialCrops && initialSelectedCrops && <CropFilter data={initialCrops} onChange={handleCropFilterChange}
                                                                             initialSelectedCrops={initialSelectedCrops} intl={intl} maxSelectable={1}/>}
                        </Grid.Column>
                    </Grid.Row>
                    <Grid.Row className={`hhi-section`}>
                        <HHILegend legends={legends} 
                                   title={intl.formatMessage({ id: 'opinionRating', defaultMessage: 'Opinion Rating' })} />
                    </Grid.Row>
                    <Grid.Row className="map-row">
                        <Grid.Column width={16}>
                            <MapComponent {...mapComponent} sources={dynamicSources} data={processedData} height={height} intl={intl} colors={mapColors}/>
                        </Grid.Column>
                    </Grid.Row>
                    <Grid.Row className={`source-section`}>
                        <Grid.Column>
                            <Source title={`Source: ${sourceText}${editing ? ` *${type}*` : ''}`} />
                        </Grid.Column>
                    </Grid.Row>
                </Grid>
            </Container>
        </div>
    )
}

const colors = [
    { upTo: 100, color: '#FF3833' },
    { upTo: 79.99, color: '#FF7E37' },
    { upTo: 59.99, color: '#FFFC61' },
    { upTo: 39.99, color: '#CCF000' },
    { upTo: 19.99, color: '#75DD00' },
];

const getColor = (value) => {
    if (value <= colors[4].upTo) {
        return colors[4].color;
    }
    if (value <= colors[3].upTo) {
        return colors[3].color;
    }
    if (value <= colors[2].upTo) {
        return colors[2].color;
    }
    if (value <= colors[1].upTo) {
        return colors[1].color;
    }
    if (value <= colors[0].upTo) {
        return colors[0].color;
    }
}

const legends = [
    {
        id: 8,
        'color': colors[0].color,
        'label': 'Extremely poor (0% - 19.99%)',
        'label-range': '',
        'label-key': 'extremely-poor-legend',
    },
    {
        id: 9,
        'color': colors[1].color,
        'label': 'Poor (20% - 39.99%)',
        'label-key': 'poor-legend',
        'label-range': '',
    },
    {
        id: 10,
        'color': colors[2].color,
        'label': 'Fair (40% - 59.99%)',
        'label-key': 'fair-legend',
        'label-range': '',
    },
    {
        id: 11,
        'color': colors[3].color,
        'label': 'Good (60% - 79.99%)',
        'label-key': 'good-legend',
        'label-range': '',
    },
    {
        id: 12,
        'color': colors[4].color,
        'label': 'Excellent (80% - 100%)',
        'label-range': '',
        'label-key': 'excellent-legend',
    }
];

const mapStateToProps = (state, ownProps) => {
    return {
        categoriesWP: state.getIn([DATA, WP_CATEGORIES]),
        countries: state.getIn([DATA, COUNTRIES_FILTER]),
        filters: state.getIn([DATA, 'filters']),
        locale: state.getIn(['intl', 'locale']),
        mapData: state.getIn([DATA, MAP_INDICATOR_DATA, DATA]),
    }
}

const mapActionCreators = {
    setDefaultFilter: setFilter,
    onLoadCategories: getWpCategories,
    onLoadIndicatorData: getMapIndicator,
    onLoadCountries: getCountries,
};

export default connect(mapStateToProps, mapActionCreators)(injectIntl(Map))
