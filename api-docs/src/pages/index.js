import clsx from 'clsx';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';

import Heading from '@theme/Heading';
import styles from './index.module.css';

function HomepageHeader() {
  const {siteConfig} = useDocusaurusContext();
  return (
    <header className={clsx('hero hero--primary', styles.heroBanner)}>
      <div className="container">
        <Heading as="h1" className="hero__title">
          {siteConfig.title}
        </Heading>
        <p className="hero__subtitle">{siteConfig.tagline}</p>
        <div className={styles.buttons}>
          <Link
            className="button button--secondary button--lg margin-horiz--sm"
            to="/cms">
            🏗️ CMS Documentation
          </Link>
          <Link
            className="button button--secondary button--lg margin-horiz--sm"
            to="/apis">
            🔌 API Documentation
          </Link>
        </div>
      </div>
    </header>
  );
}

function HomepageFeatures() {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          <div className="col col--6">
            <div className="text--center padding--md">
              <div className="card">
                <div className="card__header">
                  <h2>🏗️ CMS Features</h2>
                </div>
                <div className="card__body">
                  <p>
                    Complete documentation for Control Center CMS features including 
                    project management, service integrations, and development workflows.
                  </p>
                  <ul style={{textAlign: 'left'}}>
                    <li>Project creation and management</li>
                    <li>Service integrations</li>
                    <li>Codespace development environment</li>
                    <li>Template system</li>
                    <li>Deployment workflows</li>
                  </ul>
                </div>
                <div className="card__footer">
                  <Link
                    className="button button--primary button--block"
                    to="/cms">
                    Explore CMS Docs
                  </Link>
                </div>
              </div>
            </div>
          </div>
          
          <div className="col col--6">
            <div className="text--center padding--md">
              <div className="card">
                <div className="card__header">
                  <h2>🔌 API Integrations</h2>
                </div>
                <div className="card__body">
                  <p>
                    Comprehensive API documentation for both internal CMS functionality 
                    and external service integrations.
                  </p>
                  <ul style={{textAlign: 'left'}}>
                    <li>AI & Machine Learning APIs</li>
                    <li>Communication services</li>
                    <li>Payment processing</li>
                    <li>Data services & utilities</li>
                    <li>Development tools</li>
                  </ul>
                </div>
                <div className="card__footer">
                  <Link
                    className="button button--primary button--block"
                    to="/apis">
                    Explore API Docs
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default function Home() {
  const {siteConfig} = useDocusaurusContext();
  return (
    <Layout
      title={siteConfig.title}
      description="Comprehensive documentation for CMS features and API integrations">
      <HomepageHeader />
      <main>
        <HomepageFeatures />
      </main>
    </Layout>
  );
}
