import React from "react";
import { Input, Popup, Form, Button } from 'semantic-ui-react'
import './styles.scss';
import { injectIntl } from "react-intl";
import { connect } from "react-redux";
import { CURRENT_TAB, SELECTED_COUNTRY } from "../../../reducers/StoreConstants";

const Export = ({
                    methodology,
                    download,
                    exportPng,
                    containerRef,
                    type,
                    filters,
                    chartType,
                    selectedCrops,
                    selectedYear
                }) => {
    const indexOfHash = window.location.href.indexOf("#");
    let url = window.location.href;
    if (indexOfHash > 0) {
        url = url.substring(0, indexOfHash);
    }
    const GenerateUrlForm = () => {
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

        let finalUrl = `${url}#tab=${selectedTab}/chart=${chartType}/country=${selectedCountry}`
        if (selectedCrops && selectedCrops.length > 0) {
            finalUrl = finalUrl + `/crops=${selectedCrops.join(",")}`;
        }
        if (selectedYear && selectedYear.length > 0) {
            finalUrl = finalUrl + `/years=${selectedYear.join(",")}`;
        }
        return (<Form.Group grouped>
            <Input key="search_input" type="text" icon='search' iconPosition='left'
                   placeholder="Search..." value={finalUrl} style={{ width: '500px' }} />
            <Popup on={"click"} content={"text copied to clipboard"} closeOnTriggerClick={true}
                   trigger={<Button onClick={() => navigator.clipboard.writeText(finalUrl)}>Share</Button>} />

        </Form.Group>)
    }
    return (
        <div className="export-wrapper">
            <div className="export-buttons">
                {download === 'true'
                    ? <div className="export download" onClick={e => exportPng(containerRef, type)} />
                    : null}
                <Popup className="methods-popup" content={<GenerateUrlForm />}
                       on={"click"}
                       trigger={<div className="export share tooltip" />}
                       position='top right' />
            </div>
            {methodology
                ?
                <Popup className="methods-popup" content={methodology} trigger={<div className="tooltip">Methods</div>}
                       position='bottom right' />
                : null}
        </div>
    )

}

const mapStateToProps = (state) => {
    return { filters: state.getIn(['data', 'filters']), }
}
const mapActionCreators = {}
export default connect(mapStateToProps, mapActionCreators)(Export)
