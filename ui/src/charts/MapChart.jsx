import React, {useState} from 'react'
import {ResponsiveChoropleth} from '@nivo/geo'
import {injectIntl} from 'react-intl';
import countries from "./africa_countries.json";


import './chart.scss'

const Chart = ({height, options, intl}) => {
    debugger
    return (
        <div style={{height:height}}>
            {options && options.data && <ResponsiveChoropleth
        data={options.data}
        features={countries.features}
        margin={{ top: 0, right: 0, bottom: 0, left: 0 }}
        colors={['#C4E765', '#96C11F', '#F9D133', '#FB9755', '#FB5555']}
        label="properties.name"
        width="600"
        heigth="1300"
        domain={[0 , 5000]}
        unknownColor="#D1D2D4"
        //valueFormat=".2s"
        projectionScale={400}
        projectionTranslation={[ 0.35, 0.5 ]}
        projectionRotation={[ 0, 0, 0 ]}
        enableGraticule={false}
        borderWidth={0.5}
        borderColor="#fff"
        isInteractive={true}
        theme={{background: "#F3F9FF"}}
        legends={[
            {
                anchor: 'bottom-left',
                direction: 'column',
                justify: true,
                translateX: 20,
                translateY: -100,
                itemsSpacing: 0,
                itemWidth: 94,
                itemHeight: 18,
                itemDirection: 'left-to-right',
                itemTextColor: '#444444',
                itemOpacity: 0.85,
                symbolSize: 18,
                effects: [
                    {
                        on: 'hover',
                        style: {
                            itemTextColor: '#000000',
                            itemOpacity: 1
                        }
                    }
                ]
            }
        ]}
    />}
        </div>
    )
}

export default injectIntl(Chart)
