import React, { lazy, Suspense } from 'react';

import './app.scss';
import Spinner from './Spinner';
interface BiIntegrationProps {
  isFreemium: boolean;
}

const BiIntegration: React.FC<BiIntegrationProps> = lazy(() => import('./bi'));

const BIApp = () => {
  return (
    <Suspense fallback={<Spinner />}>
      <BiIntegration isFreemium={false} />
    </Suspense>
  );
};

export default BIApp;
