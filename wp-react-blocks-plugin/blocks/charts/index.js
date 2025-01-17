import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import BlockSave from "./BlockSave";
import BlockEdit from "./BlockEdit";
import { Chart } from '../icons/index.js'

registerBlockType(process.env.BLOCKS_NS + '/chart',
    {
        title: __('Data Chart'),
        icon: Chart,
        category: process.env.BLOCKS_CATEGORY,
        apiVersion: 2,
        attributes: {
            height: {
                type: 'number',
                default: 500,
            },
            width: {
                type: 'number',
                default: 900,
            },
            mostRecentYears: {
                type: 'number',
                default: 5,
            },
            type: {
                type: 'string',
                default: "countryInfo",
            },
            dualMode: {
                type: "Boolean",
                default: false
            },
            download: {
                type: "Boolean",
                default: true
            },
            mode: {
                type: 'String',
                default: "chart"
            },
            sources: {
                type: 'String',
                default: ""
            },
            useSourceByCategory: {
                type: 'Boolean',
                default: false
            },
            title: {
                type: 'String',
                default: ""
            },
            subTitle: {
                type: 'String',
                default: ""
            },
            defaultCountryId: {
                type: 'Number',
                default: 23
            },
            layout: {
                type: 'String',
                default: "vertical"
            },
            groupMode: {
                type: 'String',
                default: "stacked"
            },
            methodology: {
                type: 'String',
                default: "Methodology not yet defined."
            },
            totalLandArea_en: {
                type: "String",
                default: "Total land area"
            },
            totalLandArea_fr: {
                type: "String",
                default: "Total land area"
            },
            totalLandAreaUnit_en: {
                type: "String",
                default: "Hectares"
            },
            totalLandAreaUnit_fr: {
                type: "String",
                default: "Hectares"
            },
            arableLand_en: {
                type: "string",
                default: "Arable land"
            },
            arableLand_fr: {
                type: "string",
                default: "Arable land"
            },
            topHarvestedCropsAndValue_en: {
                type: "string",
                default: "Areas harvested for focus crops"
            },
            topHarvestedCropsAndValue_fr: {
                type: "string",
                default: "Superficie cultivée des cultures cible"
            },
            topHarvestedCropsAndValueUnit: {
                type: "string",
                default: "hectares"
            },
            populationVsFarmingHouseholds_en: {
                type: "string",
                default: "Population and Farming households"
            },
            populationVsFarmingHouseholds_fr: {
                type: "string",
                default: "Population et Exploitations agricoles"
            },
            totalPopulationLabel_en: {
                type: "string",
                default: "Total Population"
            },
            totalPopulationLabel_fr: {
                type: "string",
                default: "Total Population"
            },
            farmingHouseholdsLabel_en: {
                type: "string",
                default: "Farming Households"
            },
            farmingHouseholdsLabel_fr: {
                type: "string",
                default: "Farming Households"
            },
            sourceText_en: {
                type: 'String',
            },
            sourceText_fr: {
                type: 'String',
            }
        },
        edit: BlockEdit,
        save: BlockSave,
    }
);
