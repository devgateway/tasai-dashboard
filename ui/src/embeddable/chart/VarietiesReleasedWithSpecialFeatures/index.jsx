import React, {useState} from "react";
import {Grid} from "semantic-ui-react";
import {ResponsiveBar} from '@nivo/bar'
import Crops from "../common/crop";
import './styles.scss';
import Source from "../common/source";
import Filter from "../common/filter";
import Header from "../common/header";
import {getColor} from "../Countryinfo/CountryInfoChart";

const theme = {
    axis: {
        ticks: {
            text: {
                fontSize: 15,
                fill: "gray"
            },
            line: {
                stroke: "rgba(255,255,255,0)",
                strokeWidth: 0
            }
        },
        legend: {
            text: {
                fontSize: 15,
                fill: "black",
                fontWeight: 'bold'
            }
        }
    }
};

const VarietiesReleasedWithSpecialFeatures = ({data, sources}) => {

    const [selectedCrops, setSelectedCrops] = useState(null);
    const [initialCrops, setInitialCrops] = useState(null);
    const [currentData, setCurrentData] = useState(null);

    const processedData = [];

    if (!data || !data.dimensions || !data.dimensions.crop) {
        return null;
    }
    let crops = data.dimensions.crop.values;

    if (data !== currentData) {
        setCurrentData(data);
        setSelectedCrops(crops);
        setInitialCrops(crops);
    }

    // For initialization only.
    if (!initialCrops) {
        setSelectedCrops(crops);
        setInitialCrops(crops);
    } else {
        crops = selectedCrops;
    }

    const colors = [];
    const keys = [];
    crops.forEach(c => {
        let sumWF = 0;
        let sumWOF = 0;
        Object.keys(data.values[c]).forEach(i => {
            sumWF += data.values[c][i].withspecialfeature || 0;
            sumWOF += data.values[c][i].withoutspecialfeature || 0;
        });
        const key1 = 'withSpecialFeature_' + c;
        const key2 = 'withoutSpecialFeature_' + c;
        const header = {
            crop: c,
            [key1]: sumWF,
            [key2]: sumWOF,
        };
        processedData.push(header);
        keys.push(key1);
        keys.push(key2);
        colors.push(getColor({id: c.toLowerCase()}));
        colors.push(getColor({id: c.toLowerCase()}) + 80);
    });
    console.log(processedData);

    const handleCropFilterChange = (selected) => {
        const currentlySelected = [];
        for (let i = 0; i < selected.length; i++) {
            if (selected[i] === 1) {
                currentlySelected.push(initialCrops[i]);
            }
        }
        setSelectedCrops(currentlySelected);
    }

    return (
        <Grid className={`number-varieties-released`}>
            <Grid.Row className="header-section">
                <Grid.Column>
                    <Header title="Total Crops Released With and Without Special Features" subtitle=""/>
                </Grid.Column>
            </Grid.Row>
            <Grid.Row className={`filters-section`}>
                <Grid.Column>
                    <Filter data={initialCrops} onChange={handleCropFilterChange}/>
                </Grid.Column>
            </Grid.Row>
            <Grid.Row className={`crops-with-icons`}>
                <Grid.Column width={8}>
                    <Crops data={selectedCrops} title="Crops" titleClass="crops-title"/>
                </Grid.Column>
            </Grid.Row>
            <Grid.Row className={`chart-section`}>
                <Grid.Column width={16}>
                    <div style={{height: 450}}>
                        <ResponsiveBar
                            data={processedData}
                            keys={keys}
                            indexBy="crop"
                            margin={{top: 50, right: 130, bottom: 50, left: 60}}
                            padding={0.3}
                            valueScale={{type: 'linear'}}
                            indexScale={{type: 'band', round: true}}
                            colors={colors}
                            enableLabel={false}
                            borderWidth={1}
                            borderColor={{from: 'color', modifiers: [['darker', 0.4]]}}
                            axisTop={null}
                            axisRight={null}
                            axisBottom={{
                                tickSize: 5,
                                tickPadding: 5,
                                tickRotation: 0,
                                legend: 'Crops',
                                legendPosition: 'middle',
                                legendOffset: 32
                            }}
                            axisLeft={{
                                tickSize: 5,
                                tickPadding: 5,
                                tickRotation: 0,
                                legend: 'Number of Varieties Released',
                                legendPosition: 'middle',
                                legendOffset: -40
                            }}
                            tooltip={(d) => {
                                console.log(d);
                                return (<div className="tooltip-container">
                                    <div className="header-container">
                                        <div className="header">
                                            <div className="inner-container">
                                                <div className={d.indexValue.toLowerCase() + " crop-icon"}/>
                                                <div className="crop-name">{d.indexValue}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="amount-container">
                                        <table width="100%">
                                            <tbody>
                                            <tr>
                                                <td>
                                                    <span className="bold">{d.data[d.id]} out of {d.data['withSpecialFeature_' + d.indexValue.toLowerCase()] + d.data['withoutSpecialFeature_' + d.indexValue.toLowerCase()]} </span>
                                                    <span>varieties released.</span>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>)
                            }}
                        />
                    </div>
                </Grid.Column>
            </Grid.Row>
            <Grid.Row className={`source-section`}>
                <Grid.Column>
                    <Source title={"Source: " + sources}/>
                </Grid.Column>
            </Grid.Row>
        </Grid>
    )
}

export default VarietiesReleasedWithSpecialFeatures
