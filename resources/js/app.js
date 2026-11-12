import { initConnectionNotice } from './bootstrap';
import { initStory } from './story';
import { initShop, initProductDetail, initShopToolbar } from './shop';
import { initTrackClicks } from './track';

initConnectionNotice();
initStory();
initShop();
initShopToolbar();
initProductDetail();
initTrackClicks();
