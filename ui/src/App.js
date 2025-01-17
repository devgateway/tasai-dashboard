import React, { Component } from 'react';
import { Provider } from 'react-redux'
import { Redirect, Route, Switch } from 'react-router' // react-router v4/v5
import { ConnectedRouter } from 'connected-react-router/immutable'
import getStore, { history } from './redux/store'
import messages_en from "./translations/en.json";
import messages_fr from "./translations/fr.json";
import { updateIntl } from 'react-intl-redux'
import { injectIntl, IntlProvider } from "react-intl";
import smoothscroll from 'smoothscroll-polyfill';
import ResponsiveContainer from './layout'
import { getComponentByNameIgnoreCase } from "./embeddable/index";
import {
    AppContextProvider,
    Category,
    Page,
    PageConsumer,
    PageProvider,
    Post,
    PostConsumer,
    PostProvider
} from "@devgateway/wp-react-lib";
import queryString from "query-string";
import { Container, Segment } from "semantic-ui-react";
import { detectClientCountry, setFilter, fetchCustomTooltips } from "./embeddable/reducers/data";
import withTracker from "./withTracker";
import CustomMessagesProvider from "./translations/CustomMessagesProvider";
import CustomMessagesConsumer from "./translations/CustomMessagesConsumer";

const store = getStore()


// kick off the polyfill!
smoothscroll.polyfill();

const messages = {
    'en': messages_en,
    'fr': messages_fr
};

const PAGE_404_SLUG = "error-404";
const POST_404_SLUG = "post-404";


const InjectTitle = injectIntl(({ intl, locale }) => {
    document.title = intl.formatMessage({ id: 'app.title', defaultMessage: process.env.REACT_APP_TITLE });
    document.documentElement.lang = locale;
    return null
})
const InjectMessages = (props) => {
    return <IntlProvider key={props.locale} locale={props.locale}
                         messages={props.messages}>{props.children}</IntlProvider>
}

class IntlRoutes extends Component {
    componentDidMount() {
        const locale = this.props.match.params.lan ? this.props.match.params.lan.toLowerCase() : this.props.match.params.lan;
        store.dispatch(updateIntl({ locale, messages: messages[this.props.match.params.lan] }))
        store.dispatch(detectClientCountry());
        store.dispatch(fetchCustomTooltips());
        this.processSharedInfo();
    }

    componentDidUpdate() {
        const locale = this.props.match.params.lan ? this.props.match.params.lan.toLowerCase() : this.props.match.params.lan;
        store.dispatch(updateIntl({ locale, messages: messages[locale] }))
        store.dispatch(detectClientCountry());
        this.processSharedInfo();
    }

    processSharedInfo() {
        if (this.props.location.hash) {
            const sharedInformation = this.props.location.hash.substring(1, this.props.location.hash.length);
            sharedInformation.split("/").forEach(si => {
                const filterOption = si.split("=");
                store.dispatch(setFilter('share_' + filterOption[0], filterOption[1]));
            })
        }
    }

    render() {
        const locale = this.props.match.params.lan
        return (
            <CustomMessagesProvider messagesArray={messages} locale={locale}>
                <CustomMessagesConsumer>
                    <InjectMessages key={locale} locale={locale}>
                        <InjectTitle locale={locale} />
                        <AppContextProvider getComponent={getComponentByNameIgnoreCase} store={store} locale={locale}
                                            messages={messages}>
                            <Switch>
                                {
                                    //Category Route

                                }
                                <Route path="/:lan/category/:slug/">
                                    <ResponsiveContainer>
                                        <Category />
                                    </ResponsiveContainer>
                                </Route>
                                {
                                    //default route (home)
                                }
                                <Route path="/:lan" exact render={() => {
                                    return (
                                        <PageProvider
                                            slug={"home"}
                                            store={"home"}
                                            messages={messages}
                                            slug404={PAGE_404_SLUG}
                                        >
                                            <ResponsiveContainer isHome>
                                                <PageConsumer>
                                                    <Page />
                                                </PageConsumer>
                                            </ResponsiveContainer>
                                        </PageProvider>

                                    );
                                }}>
                                </Route>

                                <Route path="/:lan/dashboard/:slug" exact render={props => {
                                    return (
                                        <PageProvider
                                            slug={props.match.params.slug}
                                            store={props.match.params.slug}
                                            messages={messages}
                                            slug404={PAGE_404_SLUG}
                                        >
                                            <div className="dashboard-wrapper">
                                                <ResponsiveContainer>
                                                    <PageConsumer>
                                                        <Page />
                                                    </PageConsumer>
                                                </ResponsiveContainer>
                                            </div>
                                        </PageProvider>
                                    );
                                }}>
                                </Route>


                                <Route exact={true} path="/:lan/embeddable/:name" render={(props) => {
                                    let params = queryString.parse(props.location.search)
                                    const UIComponent = getComponentByNameIgnoreCase(props.match.params.name)

                                    return (<Container fluid={true}>
                                        {UIComponent ? <UIComponent {...params} /> :
                                            <Segment color={"red"} textAlign={"center"}><h1>Wrong Component Name</h1>
                                            </Segment>}
                                    </Container>)
                                }}>
                                </Route>


                                <Route path={"/:lan/preview/post/:id"} exact render={props => {
                                    const searchParams = new URLSearchParams(props.location.search)
                                    const preview = searchParams.get("preview")
                                    const previewNonce = searchParams.get("_wpnonce")

                                    return (
                                        <ResponsiveContainer>
                                            <PostProvider store={"preview"} perPage={1} view={preview}
                                                          previewNonce={previewNonce} previewId={props.match.params.id}>
                                                <PostConsumer>
                                                    <Post preview={true} />
                                                </PostConsumer>

                                            </PostProvider>
                                        </ResponsiveContainer>
                                    )
                                }}>
                                </Route>
                                <Route path={"/:lan/preview/page/:id"} exact render={props => {
                                    const searchParams = new URLSearchParams(props.location.search)
                                    const preview = searchParams.get("preview")
                                    const previewNonce = searchParams.get("_wpnonce")
                                    return (
                                        <ResponsiveContainer>
                                            <PageProvider store={"preview"} perPage={1} view={preview}
                                                          previewNonce={previewNonce} previewId={props.match.params.id}
                                            >
                                                <PageConsumer>
                                                    <Page preview={true} />
                                                </PageConsumer>

                                            </PageProvider>
                                        </ResponsiveContainer>
                                    )
                                }}>
                                </Route>

                                {
                                    //page route
                                }
                                <Route path="/:lan/:slug/" exact render={props => {
                                    return (

                                        <PageProvider
                                            slug={props.match.params.slug}
                                            store={props.match.params.slug}
                                            messages={messages}
                                            slug404={PAGE_404_SLUG}
                                        >
                                            <ResponsiveContainer>
                                                <PageConsumer>
                                                    <Page />
                                                </PageConsumer>
                                            </ResponsiveContainer>
                                        </PageProvider>
                                    )
                                }}>
                                </Route>
                                {
                                    //child route
                                }
                                <Route path={["/:lan/:parent/:slug/", "/:lan/:grandParent/:parent/:slug/"]} exact
                                       render={props => {
                                           return (
                                               <PageProvider
                                                   slug={props.match.params.slug}
                                                   store={props.match.params.slug}
                                                   messages={messages}
                                                   slug404={PAGE_404_SLUG}>
                                                   <ResponsiveContainer>
                                                       <PageConsumer>
                                                           <Page />
                                                       </PageConsumer>
                                                   </ResponsiveContainer>
                                               </PageProvider>

                                           );
                                       }}>

                                </Route>
                                <Route path="/:lan/type/:postType/:id/" exact render={props => {
                                    return (
                                        <ResponsiveContainer>
                                            <PostProvider
                                                id={props.match.params.id}
                                                postType={props.match.params.postType}
                                                store={props.match.params.postType + props.match.params.id}
                                                slug404={POST_404_SLUG}
                                                messages={messages}
                                            >
                                                <PostConsumer>
                                                    <Post />
                                                </PostConsumer>
                                            </PostProvider>
                                        </ResponsiveContainer>
                                    );
                                }}>
                                </Route>
                                <Route path="/:lan/:year/:month/:day/:slug/" exact render={props => (
                                    <ResponsiveContainer>
                                        <PostProvider
                                            slug={props.match.params.slug}
                                            store={props.match.params.slug}
                                            slug404={POST_404_SLUG}
                                            messages={messages}
                                        >
                                            <PostConsumer>
                                                <Post />
                                            </PostConsumer>
                                        </PostProvider>
                                    </ResponsiveContainer>
                                )}>
                                </Route>
                                {/* Special route used in Modal windows */}
                                <Route path="/:lan/:year/:month/:day/:slug/modal" exact render={props => (
                                    <PostProvider
                                        slug={props.match.params.slug}
                                        store={props.match.params.slug}
                                        slug404={POST_404_SLUG}
                                        messages={messages}>
                                        <PostConsumer>
                                            <Post />
                                        </PostConsumer>
                                    </PostProvider>
                                )}>
                                </Route>
                                <Route exact render={props => {
                                    return (
                                        <PageProvider
                                            slug={PAGE_404_SLUG}
                                            store={PAGE_404_SLUG}
                                            messages={messages}>
                                            <ResponsiveContainer>
                                                <PageConsumer>
                                                    <Page />
                                                </PageConsumer>
                                            </ResponsiveContainer>
                                        </PageProvider>
                                    );
                                }}>
                                </Route>
                            </Switch>
                        </AppContextProvider>
                    </InjectMessages>
                </CustomMessagesConsumer>
            </CustomMessagesProvider>
        )
    }
}


const MainRoutes = () => {
    return (<ConnectedRouter history={history}>

        <Switch>
            <Route component={withTracker(IntlRoutes, {})} path="/:lan" render={(props) => <IntlRoutes {...props} />} />
            <Redirect to="/en" />
        </Switch>

    </ConnectedRouter>)
}


class AppWrapper
    extends Component {
    render() {
        return (<Provider store={store}>
            <MainRoutes />
        </Provider>);
    }
}

export default AppWrapper;
