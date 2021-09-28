/**
 * Gutenberg Blocks
 *
 * All blocks related JavaScript files should be imported here.
 * You can create a new block folder in this dir and include code
 * for that block here as well.
 *
 * All blocks should be included here since this is the file that
 * Webpack is compiling as the input file.
 */

import './functions.js';

import { createForum } from "@peerboard/core";


/**
 * Create forum
 */
import create_forum from './inc/create-forum';
create_forum(createForum)

