import React, {useState} from "react";
import {ResponsiveRadar} from '@nivo/radar'
import './styles.scss';
import NoData from "../common/noData";

const FAKE_NUMBER = 0.001;
const theme = {
    axis: {
        ticks: {
            text: {
                fontSize: 12,
                fontWeight: 'bold',
                fill: "#474747"
            }
        }
    },
    dots: {
        text: {
            fontSize: 11,
            fontWeight: 'bold',
            fill: "#fff"
        }
    }
};

const ResponsiveRadarChartImpl = ({
                                      noData,
                                      processedData,
                                      keys,
                                      colors,
                                      indexBy,
                                      containerHeight = 550,
                                      maxValue = "100",
                                      margin,
                                      tooltip,
                                      intl
                                  }) => {


    const getLabel = (item) => {
        if (item === FAKE_NUMBER) {
            return intl.formatMessage({id: 'md'});
        } else {
            return item + "%";
        }
    }

    const getColors = (item) => {
        return colors.get(item.key);
    }

    return (
        <div style={{height: containerHeight}}>
            {!noData ? <ResponsiveRadar
                theme={theme}
                margin={margin || {top: 50, right: 80, bottom: 20, left: 80}}
                gridLabelOffset={22}
                data={processedData}
                indexBy={indexBy}
                keys={keys}
                maxValue={maxValue}
                dotSize={30}
                colors={(item) => getColors(item)}
                borderWidth={2}
                fillOpacity={0}
                blendMode="multiply"
                animate={true}
                motionConfig="wobbly"
                isInteractive={true}
                gridShape="linear"
                enableDotLabel={true}
                dotLabelYOffset={3}
                dotLabel={d => getLabel(d.value)}
                valueFormat={d => getLabel(d)}
                sliceTooltip={tooltip}
            /> : <NoData/>}
        </div>
    )
}

export default ResponsiveRadarChartImpl
